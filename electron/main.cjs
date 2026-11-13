const { app, BrowserWindow, Tray, Menu, nativeImage, shell, dialog, ipcMain } = require('electron');
const path = require('path');
const fs = require('fs');
const http = require('http');

// Enforce single instance lock
const gotSingleInstanceLock = app.requestSingleInstanceLock();
if (!gotSingleInstanceLock) {
  console.log('Another instance is already running. Quitting this instance.');
  app.quit();
  process.exit(0);
}

let mainWindow = null;
let tray = null;
let isQuitting = false;
let hasShownTrayBalloon = false;

// Helper to parse .env file
function parseEnvFile() {
  const envPath = path.join(__dirname, '..', '.env');
  const envConfig = {};
  if (fs.existsSync(envPath)) {
    try {
      const content = fs.readFileSync(envPath, 'utf8');
      content.split(/\r?\n/).forEach((line) => {
        const trimmed = line.trim();
        if (trimmed && !trimmed.startsWith('#')) {
          const firstEqual = trimmed.indexOf('=');
          if (firstEqual > 0) {
            const key = trimmed.slice(0, firstEqual).trim();
            const val = trimmed.slice(firstEqual + 1).trim();
            envConfig[key] = val;
          }
        }
      });
    } catch (err) {
      console.warn('Could not read .env file:', err.message);
    }
  }
  return envConfig;
}

const env = parseEnvFile();
const isDev = process.env.NODE_ENV === 'development' || !app.isPackaged;
const devHost = process.env.VITE_SERVER_HOST || env.VITE_SERVER_HOST || 'localhost';
const devPort = process.env.VITE_SERVER_PORT || env.VITE_SERVER_PORT || 5173;
const devUrl = process.env.ELECTRON_START_URL || `http://${devHost}:${devPort}`;

// App icons
const iconPath = path.join(__dirname, 'icon.png');
let appIcon = null;
let trayIcon = null;

if (fs.existsSync(iconPath)) {
  const baseImg = nativeImage.createFromPath(iconPath);
  appIcon = baseImg;
  // Windows system tray prefers 16x16, 24x24 or 32x32
  trayIcon = baseImg.resize({ width: 24, height: 24, quality: 'high' });
}

function showAndFocusWindow() {
  if (!mainWindow) {
    createMainWindow();
    return;
  }
  if (mainWindow.isMinimized()) {
    mainWindow.restore();
  }
  if (!mainWindow.isVisible()) {
    mainWindow.show();
  }
  mainWindow.focus();
}

function toggleWindow() {
  if (!mainWindow) {
    createMainWindow();
    return;
  }
  if (mainWindow.isVisible()) {
    if (mainWindow.isFocused()) {
      mainWindow.hide();
    } else {
      mainWindow.focus();
    }
  } else {
    showAndFocusWindow();
  }
}

function createTray() {
  if (tray) return;

  // Use tray icon or empty fallback
  tray = new Tray(trayIcon || nativeImage.createEmpty());
  tray.setToolTip('Vite Digital - ระบบงานสารสนเทศ');

  const contextMenu = Menu.buildFromTemplate([
    {
      label: '🏥 Vite Digital (ระบบสารสนเทศ)',
      enabled: false
    },
    { type: 'separator' },
    {
      label: '🪟 เปิดหน้าต่างหลัก (Show Window)',
      click: () => showAndFocusWindow()
    },
    {
      label: '➖ ย่อลง System Tray (Hide to Tray)',
      click: () => {
        if (mainWindow && mainWindow.isVisible()) {
          mainWindow.hide();
        }
      }
    },
    {
      label: '🔄 รีเฟรชหน้าจอ (Reload)',
      click: () => {
        if (mainWindow) {
          mainWindow.reload();
        }
      }
    },
    {
      label: '🌐 เปิดในเว็บเบราว์เซอร์ (Open in Browser)',
      click: () => {
        const currentUrl = mainWindow ? mainWindow.webContents.getURL() : devUrl;
        shell.openExternal(currentUrl || devUrl);
      }
    },
    { type: 'separator' },
    {
      label: '🛠 เครื่องมือนักพัฒนา (DevTools)',
      click: () => {
        if (mainWindow) {
          mainWindow.webContents.toggleDevTools();
        }
      }
    },
    {
      label: 'ℹ️ เกี่ยวกับโปรแกรม (About)',
      click: () => {
        dialog.showMessageBox({
          type: 'info',
          title: 'เกี่ยวกับ Vite Digital',
          message: 'Vite Digital Desktop Application',
          detail: `เวอร์ชัน: 1.0.0\nสถานะการทำงาน: System Tray Mode\nURL: ${devUrl}\nแพลตฟอร์ม: Windows Electron`,
          buttons: ['ตกลง']
        });
      }
    },
    { type: 'separator' },
    {
      label: '❌ ปิดโปรแกรมอย่างสมบูรณ์ (Exit)',
      click: () => {
        isQuitting = true;
        app.quit();
      }
    }
  ]);

  tray.setContextMenu(contextMenu);

  // Single click & Double click handlers
  tray.on('click', () => {
    toggleWindow();
  });

  tray.on('double-click', () => {
    showAndFocusWindow();
  });
}

function checkUrlAvailable(url, callback) {
  try {
    const parsed = new URL(url);
    const req = http.request(
      {
        host: parsed.hostname,
        port: parsed.port,
        path: '/',
        method: 'GET',
        timeout: 2000
      },
      (res) => {
        callback(true);
      }
    );
    req.on('error', () => callback(false));
    req.on('timeout', () => {
      req.destroy();
      callback(false);
    });
    req.end();
  } catch (e) {
    callback(false);
  }
}

function loadAppWithRetry(targetUrl, maxRetries = 15, delay = 1000) {
  let attempt = 0;

  function tryConnect() {
    attempt++;
    console.log(`Checking connection to ${targetUrl} (attempt ${attempt}/${maxRetries})...`);

    checkUrlAvailable(targetUrl, (available) => {
      if (available) {
        console.log(`Connected to dev server at ${targetUrl}`);
        if (mainWindow) {
          mainWindow.loadURL(targetUrl);
        }
      } else if (attempt < maxRetries) {
        setTimeout(tryConnect, delay);
      } else {
        // If specific host failed, try localhost as fallback
        const fallbackUrl = `http://localhost:${devPort}`;
        console.warn(`Could not reach ${targetUrl}, trying fallback ${fallbackUrl}...`);
        checkUrlAvailable(fallbackUrl, (fallbackAvailable) => {
          if (fallbackAvailable && mainWindow) {
            mainWindow.loadURL(fallbackUrl);
          } else if (mainWindow) {
            mainWindow.loadURL(targetUrl); // load anyway to show network error page
          }
        });
      }
    });
  }

  tryConnect();
}

function createMainWindow() {
  mainWindow = new BrowserWindow({
    width: 1366,
    height: 850,
    minWidth: 1024,
    minHeight: 680,
    title: 'Vite Digital - ระบบงานสารสนเทศ',
    icon: appIcon,
    autoHideMenuBar: false,
    show: false, // Don't show until ready-to-show
    webPreferences: {
      preload: path.join(__dirname, 'preload.cjs'),
      nodeIntegration: false,
      contextIsolation: true,
      sandbox: false
    }
  });

  // Native window menu
  const menuTemplate = [
    {
      label: 'ระบบงาน (File)',
      submenu: [
        {
          label: 'รีเฟรช (Reload)',
          accelerator: 'CmdOrCtrl+R',
          click: () => mainWindow.reload()
        },
        {
          label: 'เปิดในเบราว์เซอร์',
          click: () => {
            shell.openExternal(mainWindow.webContents.getURL() || devUrl);
          }
        },
        { type: 'separator' },
        {
          label: 'ย่อลง System Tray',
          accelerator: 'CmdOrCtrl+W',
          click: () => mainWindow.hide()
        },
        {
          label: 'ออกจากโปรแกรม',
          accelerator: 'CmdOrCtrl+Q',
          click: () => {
            isQuitting = true;
            app.quit();
          }
        }
      ]
    },
    {
      label: 'มุมมอง (View)',
      submenu: [
        { role: 'resetZoom', label: 'ขนาดปกติ (100%)' },
        { role: 'zoomIn', label: 'ขยาย (Zoom In)' },
        { role: 'zoomOut', label: 'ย่อ (Zoom Out)' },
        { type: 'separator' },
        { role: 'togglefullscreen', label: 'เต็มจอ (Full Screen)' },
        {
          label: 'เครื่องมือนักพัฒนา (DevTools)',
          accelerator: 'F12',
          click: () => mainWindow.webContents.toggleDevTools()
        }
      ]
    },
    {
      label: 'ช่วยเหลือ (Help)',
      submenu: [
        {
          label: 'เกี่ยวกับระบบ (About)',
          click: () => {
            dialog.showMessageBox(mainWindow, {
              type: 'info',
              title: 'เกี่ยวกับ Vite Digital',
              message: 'Vite Digital System',
              detail: 'ระบบบริหารจัดการสารสนเทศและสต๊อกคลัง\nรองรับการทำงานในโหมด Desktop System Tray'
            });
          }
        }
      ]
    }
  ];

  const menu = Menu.buildFromTemplate(menuTemplate);
  Menu.setApplicationMenu(menu);

  // Load URL or dist/index.html
  const distHtmlPath = path.join(__dirname, '..', 'dist', 'index.html');
  const isProdMode = process.argv.includes('--prod') || (app.isPackaged && fs.existsSync(distHtmlPath));

  if (isProdMode && fs.existsSync(distHtmlPath)) {
    console.log('Loading production dist build:', distHtmlPath);
    mainWindow.loadFile(distHtmlPath);
  } else {
    console.log('Loading development dev server URL:', devUrl);
    loadAppWithRetry(devUrl);
  }

  // Gracefully show window when ready
  mainWindow.once('ready-to-show', () => {
    mainWindow.show();
    mainWindow.focus();
  });

  // Intercept close button ("X") -> Hide to System Tray instead of quitting
  mainWindow.on('close', (event) => {
    if (!isQuitting) {
      event.preventDefault();
      mainWindow.hide();

      // Show notification on first hide
      if (!hasShownTrayBalloon && tray) {
        try {
          tray.displayBalloon({
            title: 'Vite Digital ยังคงทำงานอยู่',
            content: 'โปรแกรมถูกย่อมาทำงานที่ System Tray บริเวณมุมขวาล่าง คุณสามารถคลิกไอคอนเพื่อเปิดหน้าต่างได้ตลอดเวลา'
          });
        } catch (e) {
          // Balloon may not be supported on all OS configs
        }
        hasShownTrayBalloon = true;
      }
      return false;
    }
  });

  // Open external links in default system browser
  mainWindow.webContents.setWindowOpenHandler(({ url }) => {
    if (url.startsWith('http:') || url.startsWith('https:')) {
      // If external site, open in default browser
      if (!url.includes(devHost) && !url.includes('localhost') && !url.includes('127.0.0.1')) {
        shell.openExternal(url);
        return { action: 'deny' };
      }
    }
    return { action: 'allow' };
  });

  mainWindow.on('closed', () => {
    mainWindow = null;
  });
}

// IPC Events from Vue frontend
ipcMain.on('app:minimize-to-tray', () => {
  if (mainWindow) mainWindow.hide();
});

ipcMain.on('app:reload', () => {
  if (mainWindow) mainWindow.reload();
});

ipcMain.on('app:quit', () => {
  isQuitting = true;
  app.quit();
});

ipcMain.on('app:open-external', (event, url) => {
  if (url) shell.openExternal(url);
});

ipcMain.on('app:notify', (event, { title, body }) => {
  if (tray) {
    try {
      tray.displayBalloon({
        title: title || 'Vite Digital แจ้งเตือน',
        content: body || ''
      });
    } catch (e) {
      console.warn('Notification error:', e.message);
    }
  }
});

// App Lifecycle
app.whenReady().then(() => {
  createTray();
  createMainWindow();

  if (process.env.ELECTRON_TEST === '1') {
    console.log('TEST_OK: Tray and window initialized successfully');
    setTimeout(() => {
      isQuitting = true;
      app.quit();
    }, 1500);
  }

  app.on('activate', () => {
    if (BrowserWindow.getAllWindows().length === 0) {
      createMainWindow();
    } else {
      showAndFocusWindow();
    }
  });
});

// Second instance triggered: restore existing window
app.on('second-instance', () => {
  showAndFocusWindow();
});

// Before quitting
app.on('before-quit', () => {
  isQuitting = true;
});

// Prevent exiting when all windows are closed so tray remains alive
app.on('window-all-closed', () => {
  // Keep tray alive, do not call app.quit() unless on macOS
});
