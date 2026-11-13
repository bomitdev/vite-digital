# 🏥 Digital Health Hospital (Vite Digital) - โรงพยาบาลชานุมาน

ระบบสารสนเทศสุขภาพดิจิทัลและบริหารจัดการภายใน โรงพยาบาลชานุมาน (Vite 5 + Vue 3 + PHP REST API + MOPH Notify + Windows System Tray)

---

## 📑 สารบัญ (Table of Contents)
1. [ภาพรวมของระบบและโครงสร้างโปรเจกต์](#-ภาพรวมของระบบและโครงสร้างโปรเจกต์)
2. [สิ่งที่ต้องติดตั้งบนเครื่องใหม่ (Prerequisites)](#-สิ่งที่ต้องติดตั้งบนเครื่องใหม่-prerequisites)
3. [ไฟล์ที่ต้องคัดลอกมาเครื่องใหม่](#-ไฟล์ที่ต้องคัดลอกมาเครื่องใหม่)
4. [ขั้นตอนการติดตั้งและตั้งค่าบนเครื่องใหม่ (Step-by-Step)](#-ขั้นตอนการติดตั้งและตั้งค่าบนเครื่องใหม่-step-by-step)
   - [ขั้นตอนที่ 1: วางโฟลเดอร์โปรเจกต์](#ขั้นตอนที่-1-วางโฟลเดอร์โปรเจกต์)
   - [ขั้นตอนที่ 2: ตั้งค่าฐานข้อมูล (Database)](#ขั้นตอนที่-2-ตั้งค่าฐานข้อมูล-database)
   - [ขั้นตอนที่ 3: ตรวจสอบและตั้งค่าไฟล์ `.env`](#ขั้นตอนที่-3-ตรวจสอบและตั้งค่าไฟล์-env)
   - [ขั้นตอนที่ 4: ติดตั้ง Dependencies และ Build ระบบ](#ขั้นตอนที่-4-ติดตั้ง-dependencies-และ-build-ระบบ)
   - [ขั้นตอนที่ 5: ตั้งค่าระบบแจ้งเตือนอัตโนมัติ (Windows Task Scheduler)](#ขั้นตอนที่-5-ตั้งค่าระบบแจ้งเตือนอัตโนมัติ-windows-task-scheduler)
   - [ขั้นตอนที่ 6: การเปิดใช้งานระบบ (Run Application)](#ขั้นตอนที่-6-การเปิดใช้งานระบบ-run-application)
5. [ตารางสรุปไฟล์สคริปต์และหน้าที่การทำงาน (Script Reference)](#-ตารางสรุปไฟล์สคริปต์และหน้าที่การทำงาน-script-reference)
6. [การซิงค์โค้ดด้วย Git Synchronization](#-การซิงค์โค้ดด้วย-git-synchronization)
7. [การแก้ไขปัญหาที่พบบ่อย (Troubleshooting)](#-การแก้ไขปัญหาที่พบบ่อย-troubleshooting)

---

## 🏛️ ภาพรวมของระบบและโครงสร้างโปรเจกต์

ระบบประกอบด้วย 3 ส่วนหลัก:
1. **Frontend**: พัฒนาด้วย Vue 3, Vite, Vue Router, Pinia, TailwindCSS/DaisyUI
2. **Backend API**: พัฒนาด้วย PHP 8.1+ เชื่อมต่อฐานข้อมูล 3 ฐาน:
   - `DB1` (`hos`): ฐานข้อมูลหลักโรงพยาบาล (HOSxP)
   - `DB2` (`digital`): ฐานข้อมูลระบบ Digital Health (งานซ่อมทั่วไป, วัสดุไอที, บันทึกเตือน ฯลฯ)
   - `DB3` (`hosoffice`): ฐานข้อมูลระบบงานบุคคลและสารบรรณอิเล็กทรอนิกส์
3. **Background Automation & Tray Runner**: สคริปต์ PowerShell และ Windows Scheduled Task สำหรับรันแจ้งเตือน MOPH Notify และรันเซิร์ฟเวอร์แบบเบื้องหลังพร้อมไอคอน System Tray

```text
vite-digital/
├── backend/                       # PHP REST APIs, Autoload, Config & Services
│   ├── api-digital/               # API โมดูลต่างๆ (general_repair, inventory, schedule ฯลฯ)
│   ├── config.php                 # ตัวจัดการเชื่อมต่อฐานข้อมูล PDO
│   └── .env                       # ค่าคอนฟิกฐานข้อมูลและ API Keys (ห้ามอัปขึ้น Git สาธารณะ)
├── src/                           # Vue 3 Source Code (Components, Views, Router, Stores)
├── public/                        # Static Assets (รูปภาพ, โลโก้, favicon)
├── dist/                          # ผลลัพธ์จากการ Build พร้อมใช้งานจริง
├── docs/                          # เอกสารระบบ (Design, Migration, System Tray Guide)
├── data/                          # ไฟล์ข้อมูลตั้งต้น / Excel สำหรับนำเข้า
├── cron_daily_repair_notify.php   # สคริปต์แจ้งเตือนสรุปงานซ่อมประจำวัน (เวลา 16:00 น.)
├── cron_daily_schedule_notify.php # สคริปต์แจ้งเตือนตารางเวร/การลา/ไปราชการ
├── run-tray.bat                   # สคริปต์เปิดรันระบบแบบ System Tray (เบื้องหลัง)
├── stop-tray.bat                  # สคริปต์หยุดการทำงานของระบบ
├── enable-autostart.bat           # ตั้งค่าให้ระบบรันอัตโนมัติเมื่อเปิดเครื่อง
├── create-shortcut.bat            # สร้าง Shortcut ไว้บน Desktop
├── setup-daily-notify-task.ps1    # สคริปต์ลงทะเบียน Windows Task Scheduler อัตโนมัติ
├── package.json                   # การกำหนดเวอร์ชันไลบรารี Frontend
└── vite.config.js                 # ค่าคอนฟิก Vite Server & Reverse Proxy
```

---

## 💻 สิ่งที่ต้องติดตั้งบนเครื่องใหม่ (Prerequisites)

ก่อนเริ่มติดตั้งโปรเจกต์ ต้องติดตั้งโปรแกรมพื้นฐานดังต่อไปนี้บนเครื่องใหม่:

1. **XAMPP for Windows**:
   - ดาวน์โหลดเวอร์ชันที่รองรับ **PHP 8.1 หรือ PHP 8.2+**
   - แนะนำติดตั้งไว้ที่พาธเริ่มต้น: `C:\xampp`
   - เปิดใช้งาน Service: **Apache** และ **MySQL**
2. **Node.js (LTS)**:
   - ดาวน์โหลดเวอร์ชัน LTS (แนะนำ v18.x หรือ v20.x ขึ้นไป) จาก [nodejs.org](https://nodejs.org/)
   - ตรวจสอบใน Command Prompt: `node -v` และ `npm -v`
3. **Git for Windows** (จำเป็นหากต้องการใช้ระบบ Git Sync):
   - ดาวน์โหลดจาก [git-scm.com](https://git-scm.com/)

---

## 📦 ไฟล์ที่ต้องคัดลอกมาเครื่องใหม่

หากเป็นการย้ายด้วยการคัดลอกโฟลเดอร์ ให้คัดลอกไฟล์ทั้งหมด **ยกเว้น** 2 โฟลเดอร์ต่อไปนี้ (เพื่อประหยัดเวลาและพื้นที่ เนื่องจากสามารถติดตั้ง/สร้างใหม่ได้):
- ❌ **`node_modules/`** (ติดตั้งใหม่ผ่านคำสั่ง `npm install`)
- ❌ **`dist/`** (สร้างใหม่ผ่านคำสั่ง `npm run build`)

### โฟลเดอร์และไฟล์สำคัญที่ต้องมี:
- ✅ โฟลเดอร์ `backend/` (รวมถึงไฟล์ `backend/.env`)
- ✅ โฟลเดอร์ `src/`
- ✅ โฟลเดอร์ `public/`
- ✅ โฟลเดอร์ `docs/` และ `data/`
- ✅ ไฟล์คอนฟิก: `index.html`, `package.json`, `package-lock.json`, `vite.config.js`, `.env`
- ✅ ไฟล์สคริปต์รันระบบ: `run-tray.bat`, `stop-tray.bat`, `run-daily-notify.bat`, `setup-daily-notify-task.ps1`, `cron_*.php` ฯลฯ

---

## 🚀 ขั้นตอนการติดตั้งและตั้งค่าบนเครื่องใหม่ (Step-by-Step)

### ขั้นตอนที่ 1: วางโฟลเดอร์โปรเจกต์
นำไฟล์โปรเจกต์ทั้งหมดมาวางไว้ที่:
```text
C:\xampp\htdocs\vue-app\vite-digital
```
*(หากวางที่ไดรฟ์หรือโฟลเดอร์อื่น ให้ตรวจเช็คพาธในไฟล์ `.bat` และ `config.php` ให้สอดคล้องกัน)*

---

### ขั้นตอนที่ 2: ตั้งค่าฐานข้อมูล (Database)
1. เปิดโปรแกรม **XAMPP Control Panel** และกด **Start** ที่โมดูล **Apache** และ **MySQL**
2. เข้าสู่ **phpMyAdmin** (`http://localhost/phpmyadmin`) หรือใช้โปรแกรมจัดการฐานข้อมูล เช่น HeidiSQL / Navicat / DBeaver
3. นำเข้าฐานข้อมูล `digital`:
   - สร้างฐานข้อมูลใหม่ชื่อ `digital` (Collation: `utf8mb4_unicode_ci` หรือ `utf8mb4_general_ci`)
   - นำเข้าไฟล์ SQL Backup ล่าสุดของฐานข้อมูล `digital` จากเครื่องเดิม
4. ตรวจสอบการเชื่อมต่อกับฐานข้อมูลโรงพยาบาล (`hos` และ `hosoffice`):
   - ตรวจสอบให้แน่ใจว่าเครื่องใหม่สามารถเชื่อมต่อ IP ไปยังเครื่องแม่ข่ายฐานข้อมูลได้

---

### ขั้นตอนที่ 3: ตรวจสอบและตั้งค่าไฟล์ `.env`
1. ตรวจสอบไฟล์ `backend/.env`:
   ```ini
   # ฐานข้อมูล HOSxP
   DB1_HOST=192.168.3.3
   DB1_NAME=hos
   DB1_USER=your_user
   DB1_PASS=your_password

   # ฐานข้อมูล Digital Health (เครื่องนี้หรือ Server)
   DB2_HOST=127.0.0.1
   DB2_NAME=digital
   DB2_USER=root
   DB2_PASS=

   # ฐานข้อมูล HosOffice
   DB3_HOST=192.168.3.3
   DB3_NAME=hosoffice
   DB3_USER=your_user
   DB3_PASS=your_password

   # กุญแจและระบบแจ้งเตือน MOPH / หมอพร้อม
   MOPH_NOTIFY_URL=...
   MOPH_IT_CLIENT_KEY=...
   MOPH_IT_SECRET_KEY=...
   ```
2. ตรวจสอบไฟล์ `.env` ที่โฟลเดอร์หลัก (Root):
   - ตรวจสอบ IP ให้ตรงกับ IP ของเครื่องใหม่หากต้องการให้เครื่องลูกข่ายในวง LAN เข้าใช้งานได้ เช่น:
     ```ini
     VITE_BACKEND_URL=http://192.168.7.12
     VITE_API_DIGITAL_TARGET=http://192.168.7.12
     VITE_API_HOSXE_TARGET=http://192.168.7.12
     VITE_SERVER_PORT=5173
     VITE_SERVER_HOST=0.0.0.0
     ```

---

### ขั้นตอนที่ 4: ติดตั้ง Dependencies และ Build ระบบ
เปิด **PowerShell** หรือ **Command Prompt** แล้วเข้าไปที่โฟลเดอร์โปรเจกต์:

```powershell
cd C:\xampp\htdocs\vue-app\vite-digital

# 1. ติดตั้งไลบรารี Node.js
npm install

# 2. คอมไพล์และ Build โปรเจกต์ Frontend
npm run build
```
> [!NOTE]
> หลังจากรัน `npm run build` สำเร็จ ระบบจะสร้างโฟลเดอร์ `dist/` ขึ้นมาพร้อมไฟล์สำหรับใช้งานจริง

---

### ขั้นตอนที่ 5: ตั้งค่าระบบแจ้งเตือนอัตโนมัติ (Windows Task Scheduler)
ระบบมีฟังก์ชันส่งแจ้งเตือนผ่าน MOPH Notify สองส่วน:
- **ตารางเวร / การลา / ไปราชการ**: แจ้งเตือนตามเวลาที่ตั้งไว้
- **สรุปงานซ่อมทั่วไปประจำวัน**: แจ้งเตือนอัตโนมัติทุกวันเวลา 16:00 น.

**วิธีการลงทะเบียน Task อัตโนมัติ:**
1. คลิกขวาที่ปุ่ม Start ของ Windows เลือก **PowerShell (Admin)** หรือ **Terminal (Admin)**
2. พิมพ์คำสั่งแล้วกด Enter:
   ```powershell
   cd C:\xampp\htdocs\vue-app\vite-digital
   powershell -ExecutionPolicy Bypass -File .\setup-daily-notify-task.ps1
   ```
3. ระบบจะสร้าง Task ชื่อ **`ViteDigitalDailyScheduleNotify`** ใน Windows Task Scheduler ให้ทำงานเบื้องหลังอัตโนมัติ
4. *ทดสอบการทำงานด้วยตนเองได้โดย*: ดับเบิลคลิกที่ไฟล์ `run-daily-notify.bat`

---

### ขั้นตอนที่ 6: การเปิดใช้งานระบบ (Run Application)

เลือกรันได้ 2 วิธีตามรูปแบบการใช้งาน:

#### วิธีที่ 1: รันผ่าน System Tray (แนะนำสำหรับเครื่อง Server / เครื่องหลัก)
1. **สร้างทางลัดบน Desktop**: ดับเบิลคลิกไฟล์ `create-shortcut.bat` (จะมีไอคอนระบบปรากฏบนหน้าจอ Desktop)
2. **ตั้งค่าให้เปิดอัตโนมัติเมื่อเปิดคอมพิวเตอร์**: ดับเบิลคลิกไฟล์ `enable-autostart.bat`
3. **เปิดใช้งานระบบ**: ดับเบิลคลิกที่ไฟล์ **`run-tray.bat`**
   - ระบบจะทำงานเบื้องหลังและมีไอคอนแสดงอยู่ที่มุมขวาล่าง (System Tray ใกล้ๆ นาฬิกา)
   - ดับเบิลคลิกที่ไอคอน Tray เพื่อเปิดหน้าเว็บระบบได้ทันที
4. **หากต้องการปิดระบบ**: ดับเบิลคลิกไฟล์ **`stop-tray.bat`**

#### วิธีที่ 2: รันผ่านโหมดพัฒนา (Development Mode)
เหมาะสำหรับการแก้ไขโค้ดและพัฒนาต่อ:
```powershell
cd C:\xampp\htdocs\vue-app\vite-digital
npm run dev
```
เปิดเบราว์เซอร์เข้าที่: `http://localhost:5173`

---

## 📑 ตารางสรุปไฟล์สคริปต์และหน้าที่การทำงาน (Script Reference)

| ชื่อไฟล์ | ประเภท | หน้าที่การทำงาน | จังหวะที่ควรเรียกใช้ |
| :--- | :---: | :--- | :--- |
| `run-tray.bat` | Batch | สตาร์ทรันเว็บเซิร์ฟเวอร์แบบเบื้องหลัง พร้อมไอคอน System Tray | เมื่อต้องการเริ่มระบบ |
| `stop-tray.bat` | Batch | หยุดการทำงานของเซิร์ฟเวอร์และปิดไอคอน System Tray ทั้งหมด | เมื่อต้องการหยุด/รีสตาร์ทระบบ |
| `create-shortcut.bat` | Batch | สร้างไอคอนทางลัดสำหรับเข้าโปรแกรมไว้บน Desktop | รันครั้งแรกหลังติดตั้ง |
| `enable-autostart.bat` | Batch | ลงทะเบียนให้โปรแกรมเปิดตัวเองอัตโนมัติเมื่อเปิดเครื่องคอมพิวเตอร์ | รันครั้งแรกหลังติดตั้ง |
| `disable-autostart.bat` | Batch | ยกเลิกการเปิดโปรแกรมอัตโนมัติเมื่อเปิดเครื่อง | เมื่อไม่ต้องการให้เปิดอัตโนมัติ |
| `setup-daily-notify-task.ps1` | PowerShell | ลงทะเบียน Windows Task Scheduler เพื่อส่งแจ้งเตือน MOPH ทุกวัน | รันครั้งแรกผ่าน PowerShell (Admin) |
| `run-daily-notify.bat` | Batch | สคริปต์รวมรันแจ้งเตือนสรุปตารางเวรและงานซ่อม (เรียกผ่าน CLI) | Task Scheduler หรือกดทดสอบด้วยมือ |
| `cron_daily_repair_notify.php` | PHP CLI | ตรวจสอบงานซ่อมคงค้างและส่งสรุปเข้า MOPH Notify ตอน 16:00 น. | รันอัตโนมัติผ่าน Task Scheduler |
| `cron_daily_schedule_notify.php`| PHP CLI | ตรวจสอบแพทย์ออกตรวจ/การลา/ไปราชการ แล้วส่งแจ้งเตือน MOPH | รันอัตโนมัติผ่าน Task Scheduler |

---

## 🔄 การซิงค์โค้ดด้วย Git Synchronization

ระบบมีหน้าต่างจัดการ Git แบบสำเร็จรูปในตัว (Backoffice):
1. ล็อกอินเข้าสู่ระบบด้วยสิทธิ์ผู้ดูแลระบบ (Admin)
2. ไปที่เมนู **Git Synchronization** (`/git-sync`)
3. สามารถเลือกโฟลเดอร์ที่ต้องการบันทึกและส่งขึ้น GitHub:
   - ✅ Frontend (`src`)
   - ✅ Backend API (`backend`)
   - ✅ เอกสารและคู่มือ (`docs`, `README.md`)
   - ✅ ไฟล์ตั้งค่าและสคริปต์รันระบบ
   - ✅ ฐานข้อมูลตั้งต้นและสคริปต์ Cron
4. กดปุ่ม **อัพโหลด (Push)** เพื่อส่งโค้ดขึ้น GitHub หรือ **โหลดกลับมาทับ (Pull)** เพื่อดึงโค้ดล่าสุดจาก GitHub

---

## ❓ การแก้ไขปัญหาที่พบบ่อย (Troubleshooting)

### 1. เข้าหน้าเว็บแล้วเป็นหน้าจอสีขาว หรือเกิดข้อผิดพลาด 404
- ตรวจสอบว่าได้รันคำสั่ง `npm run build` หรือยัง หากยังไม่ได้รัน ให้เปิด Terminal แล้วรัน `npm run build`
- ตรวจสอบว่า Apache ใน XAMPP ทำงานปกติหรือไม่

### 2. บันทึกข้อมูลหรือเรียกดูข้อมูลไม่ได้ (เกิดข้อผิดพลาด API Connection Error)
- ตรวจสอบไฟล์ `backend/.env` ว่ารหัสผ่าน MySQL และ IP ของเครื่องฐานข้อมูล (`DB1`, `DB2`, `DB3`) ถูกต้องหรือไม่
- ทดสอบทดลองเข้า `http://localhost/vue-app/vite-digital/backend/api-digital/general_repair/get_repairs.php` ว่าตอบกลับเป็น JSON หรือไม่

### 3. แจ้งเตือนเวลา 16:00 น. ไม่ส่งข้อความ
- ตรวจสอบว่า Task ใน Windows Task Scheduler ทำงานอยู่หรือไม่ โดยเปิดโปรแกรม **Task Scheduler** แล้วค้นหา `ViteDigitalDailyScheduleNotify`
- ตรวจสอบประวัติการส่งในตาราง `repair_notify_settings` ของฐานข้อมูล `digital`

---
*จัดทำเอกสารโดย: กลุ่มงานสุขภาพดิจิทัล โรงพยาบาลชานุมาน*
