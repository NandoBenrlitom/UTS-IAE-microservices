# Kubernetes Deployment - UTS IAE Microservices

Manifest ini menggambarkan arsitektur yang sama seperti `docker-compose.yml`,
tapi dijalankan di atas Kubernetes (minikube / kind / k3s / cluster nyata).

## Komponen

| Resource | Tujuan |
|----------|--------|
| `Namespace uts-iae` | Mengisolasi semua resource tugas ini. |
| `ConfigMap laravel-config` | Variabel non-rahasia (DB host, Redis, queue connection, dst). |
| `Secret laravel-secret` | `APP_KEY`, password DB. |
| `ConfigMap mysql-init` | Script `init.sql` (membuat 3 database). |
| `mysql` (Deployment + PVC + Service) | Database persistent. |
| `redis` (Deployment + Service) | Message broker untuk Laravel Queue (asinkron). |
| `user-service`, `product-service`, `order-service` | Tiap service di-Deploy 2 replika + ClusterIP Service. |
| `queue-worker` | Deployment terpisah, consumer Redis Queue (tidak expose port). |
| `Ingress uts-ingress` | Opsional, routing path-based ke setiap service. |

## Cara menjalankan (minikube)

```bash
# 1. Jalankan minikube + arahkan docker-cli ke daemon-nya
minikube start
eval $(minikube docker-env)         # macOS/Linux
# atau di PowerShell: minikube docker-env | Invoke-Expression

# 2. Build image lokal (nama harus cocok dengan manifest)
docker build -t uts-iae/user-service:latest    ./user-service
docker build -t uts-iae/product-service:latest ./product-service
docker build -t uts-iae/order-service:latest   ./order-service

# 3. Apply seluruh manifest
kubectl apply -f k8s/

# 4. Tunggu pod siap
kubectl get pods -n uts-iae -w

# 5. Akses service
kubectl port-forward -n uts-iae svc/order-service 8003:8000
# lalu buka http://localhost:8003/api/orders
```

## Verifikasi async (Kubernetes)

```bash
# Stream log queue worker
kubectl logs -n uts-iae deploy/queue-worker -f

# POST order (jadwalkan ke Redis)
curl -X POST http://localhost:8003/api/orders \
  -H "Content-Type: application/json" \
  -d '{"user_id":1,"product_id":101}'
```
