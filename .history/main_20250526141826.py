from fastapi import FastAPI, BackgroundTasks
from pydantic import BaseModel
import uuid
import time
import logging
from fastapi.middleware.cors import CORSMiddleware

app = FastAPI()

# Enable CORS for your frontend domain(s)
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],  # Change "*" to your frontend URL(s) in production
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

logging.basicConfig(level=logging.INFO)

ocr_jobs = {}

class OCRRequest(BaseModel):
    file_url: str

def fake_ocr_job(job_id: str, file_url: str):
    logging.info(f"Processing OCR job {job_id} for file: {file_url}")
    time.sleep(10)  # Simulate OCR processing delay
    ocr_result = f"Fake OCR result for {file_url}"
    ocr_jobs[job_id]['status'] = 'completed'
    ocr_jobs[job_id]['result'] = ocr_result
    logging.info(f"OCR job {job_id} completed")

@app.post("/start")
def start_ocr(request: OCRRequest, background_tasks: BackgroundTasks):
    job_id = str(uuid.uuid4())
    ocr_jobs[job_id] = {'status': 'processing', 'result': None}
    logging.info(f"Started OCR job {job_id} for file {request.file_url}")
    background_tasks.add_task(fake_ocr_job, job_id, request.file_url)
    return {"job_id": job_id}

@app.get("/status/{job_id}")
def get_status(job_id: str):
    if job_id not in ocr_jobs:
        return {"status": "not_found"}
    job = ocr_jobs[job_id]
    return {"status": job['status'], "result": job['result']}

@app.get("/")
def root():
    return {"message": "OCR API is running. Use /start to begin OCR and /status/{job_id} to check status."}
