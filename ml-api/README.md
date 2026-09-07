# ML API — Fraud Detection Service

FastAPI service that serves the trained fraud-detection model for real-time and batch inference.

## Model

- **Candidates evaluated:** Random Forest, XGBoost, LightGBM, TabNet
- **Selected model:** LightGBM — best F1-score and fastest inference latency among the candidates
- **Decision threshold:** 0.94
- **Preprocessing:** 9-step pipeline (see `inference_pipeline.py`), including sender/receiver lookup tables (`sender_lookup.parquet`, `receiver_lookup.parquet`), categorical encoding (`label_encoders.pkl`), and missing-value imputation (`imputer.pkl`)

## Endpoints

| Endpoint         | Method | Description                              |
|------------------|--------|-------------------------------------------|
| `/predict`        | POST   | Score a single transaction                |
| `/batch-predict`  | POST   | Score a batch of transactions             |
| `/explain`        | POST   | Return feature-level explanation for a prediction |
| `/health`         | GET    | Service health check                      |

## Setup

```bash
cd ml-api
python -m venv venv

# Activate the virtual environment
venv\Scripts\activate        # Windows
source venv/bin/activate     # macOS / Linux

pip install -r requirements.txt
```

## Running the service

```bash
uvicorn fraud_api:app --reload --port 8001
```

The service will be available at `http://127.0.0.1:8001`.

## Trained Models

The `trained_models/` directory contains all artifacts needed for inference (LightGBM, XGBoost, Random Forest, TabNet, encoders, thresholds, and lookup tables). These are tracked via **Git LFS** — run `git lfs pull` after cloning to fetch them.

## Environment Variables

Copy `.env.example` to `.env` and configure as needed before running the service.
