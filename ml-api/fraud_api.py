from fastapi import FastAPI, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel
from typing import List, Optional
import traceback

from inference_pipeline import FraudDetector

app = FastAPI(title="Fraud Detection API", version="2.0.0")

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_methods=["*"],
    allow_headers=["*"],
)

detector = FraudDetector()

class Transaction(BaseModel):
    timestamp:                   Optional[str]   = None
    amount:                      Optional[float] = 0.0
    transaction_type:            Optional[str]   = None
    merchant_category:           Optional[str]   = None
    location:                    Optional[str]   = None
    payment_channel:             Optional[str]   = None
    sender_account:              Optional[str]   = None
    receiver_account:            Optional[str]   = None
    spending_deviation_score:    Optional[float] = 0.0
    velocity_score:              Optional[float] = 0.0
    geo_anomaly_score:           Optional[float] = 0.0
    time_since_last_transaction: Optional[float] = 0.0
    fraud_probability:           Optional[float] = 0.0

@app.get("/health")
def health():
    return {
        "status"    : "ok",
        "best_model": detector.best_model_info["best_model"],
        "threshold" : detector.best_model_info["threshold"],
    }

@app.post("/predict")
def predict(transaction: Transaction):
    try:
        result = detector.predict(transaction.dict())
        return result
    except Exception as e:
        traceback.print_exc()
        raise HTTPException(status_code=500, detail=str(e))

@app.post("/predict-batch")
def predict_batch(transactions: List[Transaction]):
    try:
        rows    = [t.dict() for t in transactions]
        results = detector.predict_batch(rows)

        fraud_count = sum(r["is_fraud"] for r in results)

        return {
            "results"    : results,
            "total"      : len(results),
            "fraud_count": fraud_count,
            "legit_count": len(results) - fraud_count,
            "fraud_rate" : round(fraud_count / len(results) * 100, 2) if results else 0,
        }
    except Exception as e:
        traceback.print_exc()
        raise HTTPException(status_code=500, detail=str(e))

@app.post("/explain")
def explain(transaction: Transaction):
    try:
        row     = transaction.dict()
        prob    = float(row.get("fraud_probability") or 0)
        thresh  = 0.455
        reasons = []

        amount  = float(row.get("amount") or 0)
        dev     = float(row.get("spending_deviation_score") or 0)
        vel     = float(row.get("velocity_score") or 0)
        geo     = float(row.get("geo_anomaly_score") or 0)
        tx_type = row.get("transaction_type") or ""
        channel = row.get("payment_channel") or ""

        if abs(dev) > 2:
            reasons.append(f"Abnormal spending deviation (spending_deviation = {dev:.2f})")
        elif abs(dev) > 1:
            reasons.append(f"High spending deviation (spending_deviation = {dev:.2f})")

        if vel >= 15:
            reasons.append(f"Very high transaction rate (velocity = {int(vel)})")
        elif vel >= 10:
            reasons.append(f"High transaction rate (velocity = {int(vel)})")

        if geo >= 0.7:
            reasons.append(f"Highly suspicious geographic location (geo_anomaly = {geo:.2f})")
        elif geo >= 0.5:
            reasons.append(f"Unusual geographic location (geo_anomaly = {geo:.2f})")

        if amount > 5000:
            reasons.append(f"Very large transaction amount (${amount:,.2f})")
        elif amount > 2000:
            reasons.append(f"High transaction amount (${amount:,.2f})")

        if tx_type in ["transfer", "withdrawal"] and channel in ["wire_transfer", "ACH"]:
            reasons.append(f"High-risk transaction type and channel ({tx_type} via {channel})")

        if not reasons:
            reasons.append("Model detected a suspicious pattern based on combined features")

        if prob >= 0.8:
            risk = "Very High"
        elif prob >= 0.6:
            risk = "High"
        elif prob >= thresh:
            risk = "Medium"
        else:
            risk = "Low"

        return {
            "probability": prob,
            "is_fraud"   : int(prob >= thresh),
            "threshold"  : thresh,
            "reasons"    : reasons,
            "risk_level" : risk,
        }
    except Exception as e:
        traceback.print_exc()
        raise HTTPException(status_code=500, detail=str(e))