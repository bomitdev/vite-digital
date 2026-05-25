<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Git Deploy (Production)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow-sm border-0 rounded-4">
                    <div class="card-header bg-success text-white py-3 rounded-top-4">
                        <h5 class="mb-0 fw-bold"><i class="bi bi-cloud-arrow-down me-2"></i>ดึงข้อมูลอัพเดท (Production Deploy)</h5>
                    </div>
                    <div class="card-body text-center p-5">
                        <div class="bg-warning bg-opacity-10 text-warning p-3 rounded-3 mb-4 text-start">
                            <i class="bi bi-info-circle-fill me-2"></i>หน้านี้ใช้สำหรับเครื่อง <b>Production</b> (เช่น 192.168.2.2) เพื่อดึงโค้ดล่าสุดลงเครื่องโดยไม่ต้องรัน Vite Dev Server
                        </div>
                        <button id="btnPull" class="btn btn-lg btn-success px-5 rounded-pill shadow-sm fw-bold">
                            <span id="spinner" class="spinner-border spinner-border-sm me-2 d-none"></span>
                            <i class="bi bi-download me-2" id="icon"></i> เริ่มดึงข้อมูล (Git Pull)
                        </button>
                    </div>
                    <div class="card-footer bg-dark p-0 rounded-bottom-4 overflow-hidden">
                        <div class="bg-black px-3 py-2 text-secondary small fw-bold">
                            <i class="bi bi-terminal me-2"></i>Terminal Output
                        </div>
                        <pre id="output" class="text-light p-3 mb-0 font-monospace" style="min-height: 200px; max-height: 400px; overflow-y: auto; font-size: 0.85rem;">Ready to deploy...</pre>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('btnPull').addEventListener('click', async function() {
            if(!confirm('ยืนยันการดึงข้อมูลล่าสุดจาก GitHub ลงเครื่องนี้?')) return;
            
            const btn = this;
            const spinner = document.getElementById('spinner');
            const icon = document.getElementById('icon');
            const output = document.getElementById('output');
            
            btn.disabled = true;
            spinner.classList.remove('d-none');
            icon.classList.add('d-none');
            output.innerText = "กำลังสั่งการเซิร์ฟเวอร์... โปรดรอสักครู่\n(Executing git pull...)";

            try {
                const res = await fetch('git_sync.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'force_pull' })
                });
                
                const data = await res.json();
                output.innerText = data.logs || data.message || JSON.stringify(data);
                
                if (data.status === 'success') {
                    output.innerText += "\n\n✅ อัพเดทข้อมูลสำเร็จ!";
                }
            } catch (err) {
                output.innerText = "❌ เกิดข้อผิดพลาดในการเชื่อมต่อ:\n" + err.message;
            } finally {
                btn.disabled = false;
                spinner.classList.add('d-none');
                icon.classList.remove('d-none');
            }
        });
    </script>
</body>
</html>
