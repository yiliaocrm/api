// src/utils/toast.js — 轻量 Toast，替代 ElMessage
let container = null

function getContainer() {
  if (!container) {
    container = document.createElement('div')
    container.style.cssText = [
      'position:fixed', 'top:20px', 'left:50%', 'transform:translateX(-50%)',
      'z-index:9999', 'display:flex', 'flex-direction:column',
      'align-items:center', 'gap:8px', 'pointer-events:none'
    ].join(';')
    document.body.appendChild(container)
  }
  return container
}

function show(message, type = 'info', duration = 3000) {
  const el = document.createElement('div')
  const colors = {
    success: { bg: '#f0f9eb', border: '#e1f3d8', color: '#67c23a' },
    error:   { bg: '#fef0f0', border: '#fde2e2', color: '#f56c6c' },
    warning: { bg: '#fdf6ec', border: '#faecd8', color: '#e6a23c' },
    info:    { bg: '#f4f4f5', border: '#e9e9eb', color: '#909399' },
  }
  const c = colors[type] || colors.info
  el.style.cssText = [
    `background:${c.bg}`, `border:1px solid ${c.border}`, `color:${c.color}`,
    'padding:10px 20px', 'border-radius:4px', 'font-size:14px',
    'box-shadow:0 2px 12px rgba(0,0,0,.1)', 'pointer-events:auto',
    'white-space:pre-line', 'max-width:500px', 'text-align:center',
    'opacity:1', 'transition:opacity .3s'
  ].join(';')
  el.textContent = message
  getContainer().appendChild(el)
  setTimeout(() => {
    el.style.opacity = '0'
    setTimeout(() => el.remove(), 300)
  }, duration)
}

export const toast = {
  success: (msg, duration = 3000) => show(msg, 'success', duration),
  error:   (msg, duration = 5000) => show(msg, 'error', duration),
  warning: (msg, duration = 3000) => show(msg, 'warning', duration),
  info:    (msg, duration = 3000) => show(msg, 'info', duration),
}
