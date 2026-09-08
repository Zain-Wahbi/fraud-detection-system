# Model Training — Fraud Detection

This notebook contains the full model development process behind the [`ml-api`](../ml-api/README.md) service: exploratory data analysis, feature engineering, training and comparing four candidate models, and exporting the final production model.

## Contents

`fraud_detection_training.ipynb` walks through:

1. **Data loading & EDA** — class distribution, fraud rate by month/transaction type/merchant category, behavioral feature distributions, correlation analysis
2. **Data cleaning & preprocessing** — chronological sorting, dropping non-predictive / target-leaking columns
3. **Feature engineering** — temporal risk flags and other engineered features (30+ total)
4. **Stratified train/val/test split** (80/10/10)
5. **Model training** — Random Forest, XGBoost, LightGBM, TabNet
6. **Evaluation** — confusion matrices, precision-recall curves, ROC curves, feature importance
7. **Model selection** — comparison table and justification for choosing LightGBM for production
8. **Exporting artifacts** — trained models and preprocessors saved for the `ml-api` service

## Dataset

The raw dataset (~5 million transactions) is **not included** in this repository due to its size. It's sourced from Kaggle:

**[Financial Transactions Dataset for Fraud Detection](https://www.kaggle.com/datasets/aryan208/financial-transactions-dataset-for-fraud-detection)**

To run this notebook:

1. Download the dataset from the link above
2. Place it at `./data/financial_fraud_detection_dataset.csv`
3. Run the notebook top to bottom

> This notebook was originally developed on Google Colab. The setup cell has been adapted to run with local file paths.

## Setup

```bash
cd model-training
python -m venv venv
venv\Scripts\activate        # Windows
source venv/bin/activate     # macOS / Linux

pip install -r requirements.txt
jupyter notebook fraud_detection_training.ipynb
```

## Output

Running the notebook produces:
- `./outputs/plots/` — all EDA and evaluation charts
- `./outputs/trained_models/` — trained model files and preprocessing artifacts (the same artifacts used by [`ml-api`](../ml-api/README.md))