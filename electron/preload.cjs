const { contextBridge, ipcRenderer } = require('electron');

contextBridge.exposeInMainWorld('electronAPI', {
  isElectron: true,
  platform: process.platform,
  minimizeToTray: () => ipcRenderer.send('app:minimize-to-tray'),
  showNotification: (options) => ipcRenderer.send('app:notify', options),
  openExternal: (url) => ipcRenderer.send('app:open-external', url),
  reload: () => ipcRenderer.send('app:reload'),
  quit: () => ipcRenderer.send('app:quit')
});
