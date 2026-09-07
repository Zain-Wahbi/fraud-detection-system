import os, json, warnings
import joblib
import numpy as np
import pandas as pd
from xgboost import XGBClassifier
import lightgbm as lgb
from pytorch_tabnet.tab_model import TabNetClassifier
import shap  

warnings.filterwarnings("ignore")

MODELS_DIR = os.path.join(os.path.dirname(__file__), "trained_models")
DROP_EARLY = ["transaction_id", "ip_address", "device_hash", "fraud_type"]

# Human-readable bilingual labels for the engineered features SHAP/TabNet may
# point to. Anything not listed here falls back to showing the raw column
# name, so this dict does not need to be 100% exhaustive to work correctly.
FEATURE_LABELS = {
    "amount":                      {"ar": "مبلغ المعاملة",                    "en": "Transaction amount"},
    "amount_log":                  {"ar": "مبلغ المعاملة (log)",              "en": "Transaction amount (log-scaled)"},
    "spending_deviation_score":    {"ar": "انحراف الإنفاق عن المعتاد",         "en": "Spending deviation from normal"},
    "deviation_squared":           {"ar": "شدّة انحراف الإنفاق",               "en": "Spending deviation magnitude"},
    "velocity_score":              {"ar": "سرعة/تكرار المعاملات",             "en": "Transaction velocity"},
    "geo_anomaly_score":           {"ar": "شذوذ الموقع الجغرافي",             "en": "Geographic anomaly"},
    "time_since_last_transaction": {"ar": "الوقت منذ آخر معاملة",             "en": "Time since last transaction"},
    "amount_per_velocity":         {"ar": "المبلغ نسبةً إلى سرعة المعاملات",   "en": "Amount relative to velocity"},
    "amount_x_geo":                {"ar": "تفاعل المبلغ مع شذوذ الموقع",       "en": "Amount × geo anomaly interaction"},
    "velocity_x_geo":              {"ar": "تفاعل السرعة مع شذوذ الموقع",       "en": "Velocity × geo anomaly interaction"},
    "amount_x_deviation":          {"ar": "تفاعل المبلغ مع انحراف الإنفاق",    "en": "Amount × spending deviation interaction"},
    "amount_to_avg_ratio":         {"ar": "المبلغ نسبةً لمتوسط الحساب المرسل", "en": "Amount vs sender's average"},
    "sender_avg_amount":           {"ar": "متوسط مبالغ الحساب المرسل",         "en": "Sender's average amount"},
    "sender_std_amount":           {"ar": "تذبذب مبالغ الحساب المرسل",         "en": "Sender's amount volatility"},
    "sender_degree":               {"ar": "عدد الحسابات المرتبطة بالمرسل",     "en": "Sender's network degree"},
    "sender_total_transaction":    {"ar": "إجمالي معاملات المرسل السابقة",     "en": "Sender's total past transactions"},
    "sender_fraud_transaction":    {"ar": "عدد المعاملات الاحتيالية للمرسل",   "en": "Sender's past fraud count"},
    "sender_fraud_percentage":     {"ar": "نسبة احتيال الحساب المرسل تاريخياً","en": "Sender's historical fraud rate"},
    "receiver_degree":             {"ar": "عدد الحسابات المرتبطة بالمستقبل",   "en": "Receiver's network degree"},
    "receiver_total_transaction":  {"ar": "إجمالي معاملات المستقبل السابقة",   "en": "Receiver's total past transactions"},
    "receiver_fraud_transaction":  {"ar": "عدد المعاملات الاحتيالية للمستقبل", "en": "Receiver's past fraud count"},
    "receiver_fraud_percentage":   {"ar": "نسبة احتيال الحساب المستقبل تاريخياً","en": "Receiver's historical fraud rate"},
    "is_self_transfer":            {"ar": "تحويل لنفس الحساب",                "en": "Self-transfer"},
    "is_night_transaction":        {"ar": "معاملة ليلية",                     "en": "Night-time transaction"},
    "is_weekend":                  {"ar": "معاملة في نهاية الأسبوع",           "en": "Weekend transaction"},
    "transaction_type":            {"ar": "نوع المعاملة",                     "en": "Transaction type"},
    "payment_channel":              {"ar": "قناة الدفع",                       "en": "Payment channel"},
    "merchant_category":           {"ar": "فئة التاجر",                       "en": "Merchant category"},
    "location":                    {"ar": "الموقع الجغرافي",                  "en": "Location"},
}

class FraudDetector:
    def __init__(self, models_dir: str = None):
        self.models_dir = models_dir or MODELS_DIR
        self._shap_explainers = {}  # lazy cache: model_name -> shap.TreeExplainer
        self._load_artefacts()

    def _load_artefacts(self):
        md = self.models_dir

        self.lgb_booster = lgb.Booster(model_file=os.path.join(md, "lightgbm.txt"))

        self.xgb_model = XGBClassifier()
        self.xgb_model.load_model(os.path.join(md, "xgboost.json"))

        self.rf_model  = joblib.load(os.path.join(md, "random_forest.pkl"))
        self.imputer   = joblib.load(os.path.join(md, "imputer.pkl"))
        self.label_encoders = joblib.load(os.path.join(md, "label_encoders.pkl"))

        # ✅ TabNet — يُحمَّل من tabnet.zip
        self.tabnet_model = TabNetClassifier()
        self.tabnet_model.load_model(os.path.join(md, "tabnet.zip"))

        with open(os.path.join(md, "feature_names.json"))   as f: self.feature_names  = json.load(f)
        with open(os.path.join(md, "cat_cols.json"))        as f: self.cat_cols        = json.load(f)
        with open(os.path.join(md, "thresholds.json"))      as f: self.thresholds      = json.load(f)
        with open(os.path.join(md, "best_model.json"))      as f: self.best_model_info = json.load(f)
        with open(os.path.join(md, "global_fallback.json")) as f: self.fallback        = json.load(f)

        self.sender_lookup   = pd.read_parquet(os.path.join(md, "sender_lookup.parquet"))
        self.receiver_lookup = pd.read_parquet(os.path.join(md, "receiver_lookup.parquet"))

        print(f"✅ FraudDetector loaded — best model: {self.best_model_info['best_model']}")

    def _preprocess(self, df: pd.DataFrame) -> pd.DataFrame:
        df = df.copy()

        drop_cols = [c for c in DROP_EARLY if c in df.columns]
        df.drop(columns=drop_cols, inplace=True, errors="ignore")
        df.drop(columns=[c for c in df.columns if "Unnamed" in c], inplace=True, errors="ignore")

        if "timestamp" in df.columns:
            df["timestamp"]   = pd.to_datetime(df["timestamp"], errors="coerce")
            df["hour"]        = df["timestamp"].dt.hour.fillna(0).astype(int)
            df["day"]         = df["timestamp"].dt.day.fillna(1).astype(int)
            df["day_of_week"] = df["timestamp"].dt.weekday.fillna(0).astype(int)
            df["month"]       = df["timestamp"].dt.month.fillna(1).astype(int)

        for col in self.cat_cols:
            if col not in df.columns:
                continue
            le = self.label_encoders.get(col)
            if le is None:
                df[col] = 0
                continue
            known = set(le.classes_)
            df[col] = df[col].astype(str).apply(lambda x: x if x in known else le.classes_[0])
            df[col] = le.transform(df[col])

        # NOTE: The imputer step used to run HERE, before feature engineering.
        # That was the root bug: self.imputer.feature_names_in_ contains the
        # full 50+ engineered feature set (sender_avg_amount, sender_fraud_percentage,
        # receiver_fraud_percentage, etc.), which do not exist in df yet at this
        # point. Pre-filling them with 0 caused a column-name collision later when
        # merging sender_lookup/receiver_lookup (pandas suffixes the duplicate
        # columns as _x/_y), which made the fallback-fill logic silently overwrite
        # every transaction's account-history features with the SAME constant
        # fallback value instead of the real per-account value. That wiped out the
        # model's strongest fraud signal and produced the bimodal 0% / 90%+ output.
        #
        # Fix: engineer ALL features first, then run the imputer once at the end
        # on the fully-built feature matrix (see bottom of this function).

        df["is_night_transaction"] = df["hour"].between(18, 24).astype(np.int8) if "hour" in df.columns else 0
        df["is_weekend"]           = df["day_of_week"].isin([5, 6]).astype(np.int8) if "day_of_week" in df.columns else 0

        df["amount_log"]          = np.log1p(df["amount"])
        df["amount_per_velocity"] = df["amount"] / (df.get("velocity_score", pd.Series(1, index=df.index)) + 1)

        sender_cols   = ["sender_avg_amount","sender_std_amount","sender_degree",
                         "sender_total_transaction","sender_fraud_transaction","sender_fraud_percentage"]
        receiver_cols = ["receiver_degree","receiver_total_transaction",
                         "receiver_fraud_transaction","receiver_fraud_percentage"]

        if "sender_account" in df.columns:
            df = df.merge(self.sender_lookup, on="sender_account", how="left")
            for col in sender_cols:
                df[col] = df[col].fillna(self.fallback.get(col, 0)) if col in df.columns else self.fallback.get(col, 0)
            df["amount_to_avg_ratio"] = (df["amount"] / df["sender_avg_amount"].replace(0, np.nan)).fillna(self.fallback.get("amount_to_avg_ratio", 1.0))
        else:
            for col in sender_cols: df[col] = self.fallback.get(col, 0)
            df["amount_to_avg_ratio"] = self.fallback.get("amount_to_avg_ratio", 1.0)

        if "receiver_account" in df.columns:
            df = df.merge(self.receiver_lookup, on="receiver_account", how="left")
            for col in receiver_cols:
                df[col] = df[col].fillna(self.fallback.get(col, 0)) if col in df.columns else self.fallback.get(col, 0)
        else:
            for col in receiver_cols: df[col] = self.fallback.get(col, 0)

        if "sender_account" in df.columns and "receiver_account" in df.columns:
            df["is_self_transfer"] = (df["sender_account"] == df["receiver_account"]).astype(np.int8)
        else:
            df["is_self_transfer"] = 0

        df["transaction_per_day"] = self.fallback.get("transaction_per_day", 1.0)
        df["transaction_gap"]     = self.fallback.get("transaction_gap", 0.0)

        df["deviation_squared"]  = df["spending_deviation_score"] ** 2
        df["amount_x_geo"]       = df["amount_log"] * df["geo_anomaly_score"]
        df["velocity_x_geo"]     = df["velocity_score"] * df["geo_anomaly_score"]
        df["amount_x_deviation"] = df["amount_log"] * df["spending_deviation_score"].abs()

        for col in ["is_fraud","timestamp","sender_account","receiver_account",
                    "transaction_id","ip_address","device_hash","fraud_type"]:
            if col in df.columns: df.drop(columns=[col], inplace=True)

        # ✅ Imputer now runs AFTER all feature engineering is complete, on the
        # real engineered values (not zero placeholders). Missing expected
        # columns are filled with NaN (not 0) so the imputer can apply its
        # fitted statistic (mean/median/etc.) instead of silently treating
        # "missing" as "zero".
        if hasattr(self.imputer, "feature_names_in_"):
            expected = list(self.imputer.feature_names_in_)
        else:
            expected = df.select_dtypes(include=np.number).columns.tolist()
            if "is_fraud" in expected: expected.remove("is_fraud")

        for col in expected:
            if col not in df.columns:
                df[col] = np.nan

        # Compatibility patch: the pickled SimpleImputer was fit under an older
        # scikit-learn version that stored this internal attribute as
        # `_fit_dtype`, while the installed scikit-learn version at inference
        # time looks for `_fill_dtype`. Without this, .transform() raises
        # AttributeError. This does not affect imputation logic/values.
        if hasattr(self.imputer, '_fit_dtype') and not hasattr(self.imputer, '_fill_dtype'):
            self.imputer._fill_dtype = self.imputer._fit_dtype

        X_imp = self.imputer.transform(df[expected])
        for i, col in enumerate(expected):
            df[col] = X_imp[:, i]

        for col in self.feature_names:
            if col not in df.columns: df[col] = 0
        df = df[self.feature_names]

        return df

    def predict_batch(self, rows: list, model_name: str = None) -> list:
        if model_name is None:
            model_name = self.best_model_info["best_model"]

        df        = pd.DataFrame(rows)
        threshold = self.thresholds.get(model_name, 0.5)
        X         = self._preprocess(df)

        if model_name == "LightGBM":
            proba = self.lgb_booster.predict(X)
        elif model_name == "XGBoost":
            proba = self.xgb_model.predict_proba(X)[:, 1]
        elif model_name == "Random Forest":
            proba = self.rf_model.predict_proba(X)[:, 1]
        elif model_name == "TabNet":
            # ✅ TabNet يحتاج numpy float32
            X_tn  = X.values.astype(np.float32)
            proba = self.tabnet_model.predict_proba(X_tn)[:, 1]
        else:
            raise ValueError(f"Unknown model: {model_name}")

        results = []
        for p in proba:
            results.append({
                "is_fraud"   : int(p >= threshold),
                "probability": round(float(p), 6),
                "model_used" : model_name,
                "threshold"  : threshold,
            })
        return results

    def predict(self, row: dict, model_name: str = None) -> dict:
        return self.predict_batch([row], model_name=model_name)[0]

    # ------------------------------------------------------------------
    # ✅ Real, model-driven explanation (replaces the old hardcoded rules).
    # For tree models (LightGBM/XGBoost/Random Forest) we use SHAP, which
    # gives the exact per-feature contribution to THIS model's THIS decision.
    # For TabNet we use its built-in attention-based explain(), since SHAP's
    # TreeExplainer does not apply to it.
    # ------------------------------------------------------------------

    def _get_underlying_model(self, model_name: str):
        if model_name == "LightGBM":       return self.lgb_booster
        if model_name == "XGBoost":        return self.xgb_model
        if model_name == "Random Forest":  return self.rf_model
        raise ValueError(f"No SHAP-compatible model for: {model_name}")

    def _shap_contributions(self, model_name: str, X: pd.DataFrame) -> dict:
        # Explainers are expensive to build, so build once per model and reuse.
        if model_name not in self._shap_explainers:
            model = self._get_underlying_model(model_name)
            self._shap_explainers[model_name] = shap.TreeExplainer(model)
        explainer = self._shap_explainers[model_name]

        shap_out = explainer.shap_values(X)
        if isinstance(shap_out, list):
            # e.g. sklearn RandomForestClassifier -> [class_0_values, class_1_values]
            row_values = shap_out[1][0]
        else:
            arr = np.asarray(shap_out)
            if arr.ndim == 3:
                # (n_samples, n_features, n_classes) shape used by newer shap versions
                row_values = arr[0, :, -1]
            else:
                row_values = arr[0]

        return dict(zip(X.columns, row_values))

    def _tabnet_contributions(self, X: pd.DataFrame) -> dict:
        X_tn = X.values.astype(np.float32)
        explain_matrix, _masks = self.tabnet_model.explain(X_tn)
        # TabNet's explain() returns attention-based feature magnitudes
        # (always >= 0) — "how much the model focused on this feature" rather
        # than a signed push toward/away from fraud like SHAP gives.
        return dict(zip(X.columns, explain_matrix[0]))

    def explain(self, row: dict, model_name: str = None, top_n: int = 5) -> dict:
        """
        Returns fraud reasons based on what the SELECTED model actually used
        to make ITS decision — not on independent hardcoded thresholds.
        """
        if model_name is None:
            model_name = self.best_model_info["best_model"]

        threshold = self.thresholds.get(model_name, 0.5)
        df = pd.DataFrame([row])
        X  = self._preprocess(df)

        # Same prediction logic as predict_batch, so probability/threshold
        # shown here always match what was stored for this transaction.
        if model_name == "LightGBM":
            proba = float(self.lgb_booster.predict(X)[0])
        elif model_name == "XGBoost":
            proba = float(self.xgb_model.predict_proba(X)[:, 1][0])
        elif model_name == "Random Forest":
            proba = float(self.rf_model.predict_proba(X)[:, 1][0])
        elif model_name == "TabNet":
            X_tn  = X.values.astype(np.float32)
            proba = float(self.tabnet_model.predict_proba(X_tn)[:, 1][0])
        else:
            raise ValueError(f"Unknown model: {model_name}")

        is_fraud = int(proba >= threshold)

        if model_name == "TabNet":
            contributions = self._tabnet_contributions(X)
        else:
            contributions = self._shap_contributions(model_name, X)

        # Rank by strength of contribution; prefer features pushing TOWARD
        # fraud (positive SHAP value / high TabNet attention on a fraud case).
        ranked = sorted(contributions.items(), key=lambda kv: -abs(kv[1]))
        top_features = [(name, val) for name, val in ranked if val > 0][:top_n]
        if not top_features:
            top_features = ranked[:top_n]

        reasons_ar, reasons_en = [], []
        for name, val in top_features:
            label = FEATURE_LABELS.get(name, {"ar": name, "en": name})
            raw_value = row.get(name)
            value_str = f" ({raw_value})" if name in row and raw_value is not None else ""
            reasons_ar.append(f"{label['ar']}{value_str} — ساهمت في قرار النموذج")
            reasons_en.append(f"{label['en']}{value_str} — contributed to the model's decision")

        if proba >= 0.8:          risk = "Very High"
        elif proba >= 0.6:        risk = "High"
        elif proba >= threshold:  risk = "Medium"
        else:                     risk = "Low"

        return {
            "probability":   round(proba, 6),
            "is_fraud":      is_fraud,
            "threshold":     threshold,
            "model_used":    model_name,
            "risk_level":    risk,
            "reasons":       reasons_ar,
            "reasons_en":    reasons_en,
            "top_features":  [{"feature": n, "contribution": round(float(v), 6)} for n, v in top_features],
        }