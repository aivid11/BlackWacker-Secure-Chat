document.addEventListener('DOMContentLoaded', function () {
  const container = document; // یا document.querySelector('.chat-container') اگر دارید
  // جلوگیری از منوی طولانی و شروع سلکت
  container.addEventListener('selectstart', function(e){
    // فقط وقتی که داخل پیام است جلوگیری کن
    if (e.target.closest && e.target.closest('.message')) {
      e.preventDefault();
    }
  });

  container.addEventListener('contextmenu', function(e){
    if (e.target.closest && e.target.closest('.message')) {
      e.preventDefault();
    }
  });

  // اگر مرورگر یا کد دیگری selection ایجاد کرد، آن را پاک کن
  document.addEventListener('selectionchange', function () {
    const sel = window.getSelection && window.getSelection();
    if (!sel) return;
    if (sel.rangeCount === 0) return;
    // اگر انتخاب داخل چت/پیام است، پاکش کن
    const anchor = sel.anchorNode;
    if (!anchor) return;
    const parent = anchor.nodeType === 3 ? anchor.parentElement : anchor;
    if (parent && parent.closest && parent.closest('.message')) {
      sel.removeAllRanges();
    }
  });

  // Touch -> click fallback (فقط اگر احساس کردی کلیک در موبایل کار نمی‌کند)
  // این بخش خیلی محکم preventDefault نمیکنه تا کلیک طبیعی حفظ شود.
  let touchStartInfo = null;
  container.addEventListener('touchstart', function(e) {
    const t = e.touches[0];
    if (!t) return;
    const el = e.target.closest && e.target.closest('.message');
    if (el) {
      touchStartInfo = {
        el,
        x: t.clientX,
        y: t.clientY,
        time: Date.now()
      };
    } else {
      touchStartInfo = null;
    }
  }, {passive: true});

  container.addEventListener('touchend', function(e) {
    if (!touchStartInfo) return;
    const touch = e.changedTouches && e.changedTouches[0];
    if (!touch) return;
    const dx = Math.abs(touch.clientX - touchStartInfo.x);
    const dy = Math.abs(touch.clientY - touchStartInfo.y);
    const dt = Date.now() - touchStartInfo.time;
    // شرط: تاچ کوتاه و حرکت کم — آنگاه شبیه‌سازی کلیک
    if (dx < 10 && dy < 10 && dt < 500) {
      // اگر عنصر دارای handler کلیک است، آن را با dispatchEvent فعال می‌کنیم
      const clickEvent = new MouseEvent('click', {
        view: window,
        bubbles: true,
        cancelable: true
      });
      touchStartInfo.el.dispatchEvent(clickEvent);
    }
    touchStartInfo = null;
  }, {passive: true});
});