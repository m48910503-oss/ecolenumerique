document.addEventListener('DOMContentLoaded', () => {
  const textarea = document.getElementById('report_reason');
  const counter = document.getElementById('report-count');
  if (textarea && counter) {
    const update = () => {
      counter.textContent = textarea.value.length.toString();
    };
    textarea.addEventListener('input', update);
    update();
  }
});
