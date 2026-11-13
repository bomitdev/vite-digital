# 🖥️ คู่มือการใช้งาน Windows System Tray (Vite Digital / CNMH DigitalHealth)

ระบบ **System Tray** นี้พัฒนาขึ้นด้วย **Windows Native Shell (VBScript + PowerShell Windows Forms NotifyIcon)** ตามสถาปัตยกรรมเดียวกับ **WF SmartDose Launcher** เพื่อให้สามารถรันเว็บแอปพลิเคชันอยู่เบื้องหลังแบบเงียบสนิท (Silent / Zero Black Window) พร้อมไอคอนถาดระบบมุมขวาล่างของ Windows

---

## 🌟 จุดเด่นและฟังก์ชันการทำงาน

1. **ทำงานเบื้องหลังแบบเงียบสนิท 100% (Silent Launch)**:
   - รันผ่าน `wscript.exe` และ `powershell.exe -WindowStyle Hidden` ไม่มีหน้าต่าง Command Prompt (จอดำ) โผล่มากวนใจ

2. **System Tray Icon (ถาดระบบมุมขวาล่าง)**:
   - **ดับเบิ้ลคลิกที่ไอคอน**: เปิดเว็บเบราว์เซอร์เข้าสู่ระบบทันที (`http://localhost:5173` หรือ IP ตาม `.env`)
   - **คลิกขวาที่ไอคอน**: แสดงเมนูคำสั่งครบวงจร
     - 🌐 **เปิดโปรแกรม (Open Web App)**: เปิด Browser ทันที
     - 📋 **คัดลอก URL**: คัดลอกลิงก์เข้าระบบลง Clipboard พร้อมแจ้งเตือน Balloon Tip
     - 🔄 **รีสตาร์ทเซิร์ฟเวอร์ (Restart Server)**: รีสตาร์ท Process เซิร์ฟเวอร์ในคลิกเดียว
     - 📂 **เปิดโฟลเดอร์โปรแกรม (Open Folder)**: เปิด File Explorer มายังโฟลเดอร์โปรเจกต์
     - ❌ **ออกจากโปรแกรม (Exit)**: ปิด Server และถอดไอคอน Tray อย่างสมบูรณ์

3. **Single-Instance Protection (ป้องกันการเปิดซ้ำ)**:
   - ใช้ **Global Mutex** ตรวจจับ หากโปรแกรมทำงานอยู่ใน System Tray อยู่แล้ว เมื่อสั่งรันซ้ำ ระบบจะเปิดหน้าต่างเบราว์เซอร์ขึ้นมาให้ทันที และปิด instance ใหม่อย่างเงียบๆ โดยไม่เกิดการรันซ้ำซ้อน

4. **ตรวจจับพอร์ตและการเชื่อมต่ออัตโนมัติ**:
   - อ่านค่าพอร์ตและ IP จาก `.env` (`VITE_SERVER_PORT` / `VITE_SERVER_HOST`)
   - หากมีเซิร์ฟเวอร์กำลังรันอยู่แล้ว จะเชื่อมต่อทันทีโดยไม่สั่งทับซ้อน

---

## 🚀 ไฟล์คำสั่งและวิธีใช้งาน

| ชื่อไฟล์ | การทำงาน |
| :--- | :--- |
| **`run-tray.bat`** | ดับเบิ้ลคลิกเพื่อเริ่มทำงาน System Tray (ส่งคำสั่งให้ `run-tray.vbs` แล้วปิด CMD ทันที) |
| **`run-tray.vbs`** | ตัวเปิดเบื้องหลังแบบไร้หน้าต่างจอดำ (รัน `tray-runner.ps1` แบบ `WindowStyle Hidden`) |
| **`tray-runner.ps1`** | สคริปต์หลัก PowerShell สร้าง NotifyIcon, จัดการ Lifecycle, ควบคุม Node/Vite Server |
| **`stop-tray.bat`** | ดับเบิ้ลคลิกเพื่อหยุดการทำงานของ System Tray และ Server ทั้งหมดทันที |
| **`create-shortcut.bat`** | สร้าง Shortcut **Vite Digital** ไว้บน Desktop ของผู้ใช้ พร้อมไอคอน `app.ico` |
| **`enable-autostart.bat`** | ตั้งค่าให้โปรแกรมเริ่มทำงานใน System Tray อัตโนมัติทุกครั้งที่เปิดเครื่อง Windows |
| **`disable-autostart.bat`** | ยกเลิกการเปิดโปรแกรมอัตโนมัติเมื่อเปิดเครื่อง |

---

## 💻 วิธีการรันผ่านคำสั่ง npm

นอกจากดับเบิ้ลคลิกไฟล์ข้างต้นแล้ว ยังสามารถสั่งผ่าน Terminal ได้เช่นกัน:

```bash
# เริ่มรัน System Tray แบบ Silent
npm run tray

# หยุดการทำงานของ System Tray และ Server
npm run stop
```

---

## 📁 โครงสร้างสถาปัตยกรรมไฟล์

```text
vite-digital/
├── run-tray.bat               # ตัวสตาร์ท Launcher
├── run-tray.vbs               # Silent VBScript Runner
├── tray-runner.ps1            # PowerShell NotifyIcon & Process Manager
├── stop-tray.bat              # สคริปต์หยุดระบบ
├── create-shortcut.bat        # ตัวสร้าง Desktop Shortcut
├── create-desktop-shortcut.ps1# Logic สร้าง Desktop Shortcut
├── enable-autostart.bat       # ตัวเปิด Auto-start
├── enable-autostart.ps1       # Logic สร้าง Startup Shortcut
├── disable-autostart.bat      # ตัวปิด Auto-start
├── public/
│   └── app.ico                # ไอคอนโปรแกรมสำหรับ System Tray และ Shortcut
├── tray.log                   # บันทึกสถานะการทำงาน (Log)
└── SYSTEM_TRAY_GUIDE.md       # คู่มือฉบับนี้
```
