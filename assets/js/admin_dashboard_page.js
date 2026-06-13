(function () {
  "use strict";

  var root = document.documentElement;
  var body = document.body;

  function setTheme(theme) {
    var nextTheme = theme === "dark" ? "dark" : "light";
    root.classList.toggle("dark", nextTheme === "dark");
    root.classList.toggle("light", nextTheme === "light");
    root.setAttribute("data-theme", nextTheme);
    root.style.colorScheme = nextTheme;

    try {
      localStorage.setItem("theme", nextTheme);
    } catch (_error) {
      // Storage can be unavailable in private modes, but the visible theme still changed.
    }

    updateThemeLabels(nextTheme);
  }

  function updateThemeLabels(theme) {
    var labels = document.querySelectorAll("[data-theme-label]");
    labels.forEach(function (label) {
      label.textContent = theme === "dark" ? "Mode clair" : "Mode sombre";
    });
  }

  function initializeThemeToggles() {
    updateThemeLabels(root.classList.contains("dark") ? "dark" : "light");

    document.querySelectorAll("[data-admin-theme-toggle]").forEach(function (button) {
      button.addEventListener("click", function () {
        setTheme(root.classList.contains("dark") ? "light" : "dark");
        drawAllCharts();
      });
    });
  }

  function setSidebarOpen(open) {
    body.classList.toggle("sidebar-open", open);
    document.querySelectorAll("[data-admin-menu-toggle]").forEach(function (button) {
      button.setAttribute("aria-expanded", open ? "true" : "false");
    });
  }

  function initializeMobileSidebar() {
    document.querySelectorAll("[data-admin-menu-toggle]").forEach(function (button) {
      button.addEventListener("click", function () {
        setSidebarOpen(!body.classList.contains("sidebar-open"));
      });
    });

    document.querySelectorAll("[data-admin-menu-close]").forEach(function (button) {
      button.addEventListener("click", function () {
        setSidebarOpen(false);
      });
    });

    document.addEventListener("keydown", function (event) {
      if (event.key === "Escape") {
        setSidebarOpen(false);
      }
    });
  }

  function readJsonAttribute(element, attributeName) {
    var raw = element.getAttribute(attributeName) || "[]";
    try {
      var parsed = JSON.parse(raw);
      return Array.isArray(parsed) ? parsed : [];
    } catch (_error) {
      return [];
    }
  }

  function resolveChartColors() {
    var dark = root.classList.contains("dark");
    return {
      axis: dark ? "rgba(148, 163, 184, 0.34)" : "rgba(148, 163, 184, 0.45)",
      fill: dark ? "rgba(96, 165, 250, 0.16)" : "rgba(59, 130, 246, 0.10)",
      label: dark ? "#94a3b8" : "#64748b",
      line: dark ? "#60a5fa" : "#2563eb",
      point: dark ? "#22d3ee" : "#06b6d4"
    };
  }

  function drawChart(canvas) {
    var labels = readJsonAttribute(canvas, "data-chart-labels");
    var values = readJsonAttribute(canvas, "data-chart-values").map(function (value) {
      return Number(value) || 0;
    });

    if (labels.length === 0 || values.length === 0) {
      return;
    }

    var parent = canvas.parentElement;
    var rect = parent ? parent.getBoundingClientRect() : canvas.getBoundingClientRect();
    var width = Math.max(320, Math.floor(rect.width || 0));
    var height = Math.max(220, Math.floor(rect.height || 0));
    var ratio = window.devicePixelRatio || 1;
    var ctx = canvas.getContext("2d");

    if (!ctx) {
      return;
    }

    canvas.width = Math.floor(width * ratio);
    canvas.height = Math.floor(height * ratio);
    canvas.style.width = width + "px";
    canvas.style.height = height + "px";
    ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
    ctx.clearRect(0, 0, width, height);

    var colors = resolveChartColors();
    var padding = { top: 18, right: 18, bottom: 34, left: 42 };
    var chartWidth = width - padding.left - padding.right;
    var chartHeight = height - padding.top - padding.bottom;
    var maxValue = Math.max.apply(null, values.concat([1]));
    var roundedMax = Math.max(1, Math.ceil(maxValue / 5) * 5);
    var xStep = chartWidth / Math.max(1, values.length - 1);

    ctx.font = '12px "Inter", system-ui, sans-serif';
    ctx.lineWidth = 1;

    for (var grid = 0; grid <= 4; grid += 1) {
      var y = padding.top + chartHeight - (chartHeight * grid / 4);
      var value = Math.round(roundedMax * grid / 4);
      ctx.strokeStyle = colors.axis;
      ctx.beginPath();
      ctx.moveTo(padding.left, y);
      ctx.lineTo(width - padding.right, y);
      ctx.stroke();
      ctx.fillStyle = colors.label;
      ctx.textAlign = "right";
      ctx.fillText(String(value), padding.left - 10, y + 4);
    }

    var points = values.map(function (value, index) {
      var x = padding.left + (xStep * index);
      var y = padding.top + chartHeight - ((value / roundedMax) * chartHeight);
      return { x: x, y: y };
    });

    ctx.beginPath();
    points.forEach(function (point, index) {
      if (index === 0) {
        ctx.moveTo(point.x, point.y);
      } else {
        ctx.lineTo(point.x, point.y);
      }
    });
    ctx.lineTo(points[points.length - 1].x, padding.top + chartHeight);
    ctx.lineTo(points[0].x, padding.top + chartHeight);
    ctx.closePath();
    ctx.fillStyle = colors.fill;
    ctx.fill();

    ctx.beginPath();
    points.forEach(function (point, index) {
      if (index === 0) {
        ctx.moveTo(point.x, point.y);
      } else {
        ctx.lineTo(point.x, point.y);
      }
    });
    ctx.strokeStyle = colors.line;
    ctx.lineWidth = 3;
    ctx.lineJoin = "round";
    ctx.lineCap = "round";
    ctx.stroke();

    points.forEach(function (point) {
      ctx.beginPath();
      ctx.arc(point.x, point.y, 4, 0, Math.PI * 2);
      ctx.fillStyle = colors.point;
      ctx.fill();
      ctx.lineWidth = 2;
      ctx.strokeStyle = root.classList.contains("dark") ? "#111827" : "#ffffff";
      ctx.stroke();
    });

    ctx.fillStyle = colors.label;
    ctx.textAlign = "center";
    labels.forEach(function (label, index) {
      var point = points[index];
      if (point) {
        ctx.fillText(String(label), point.x, height - 12);
      }
    });
  }

  function drawAllCharts() {
    document.querySelectorAll("[data-admin-chart]").forEach(drawChart);
  }

  function initializeCharts() {
    drawAllCharts();
    window.addEventListener("resize", drawAllCharts);

    if ("MutationObserver" in window) {
      new MutationObserver(drawAllCharts).observe(root, {
        attributes: true,
        attributeFilter: ["class", "data-theme"]
      });
    }
  }

  function initialize() {
    initializeThemeToggles();
    initializeMobileSidebar();
    initializeCharts();
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initialize, { once: true });
  } else {
    initialize();
  }
})();
