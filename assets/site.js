document.querySelectorAll('.reveal').forEach((el, i) => {
  el.style.animationDelay = `${Math.min(i * 90, 450)}ms`;
});
