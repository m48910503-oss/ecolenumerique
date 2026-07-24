(function () {
  "use strict";

  function escapeHtml(value) {
    return String(value || "").replace(/[&<>"']/g, function (char) {
      return ({
        "&": "&amp;",
        "<": "&lt;",
        ">": "&gt;",
        '"': "&quot;",
        "'": "&#39;"
      })[char] || char;
    });
  }

  function normalizeText(value) {
    return String(value || "")
      .toLowerCase()
      .normalize("NFD")
      .replace(/[\u0300-\u036f]/g, "")
      .trim();
  }

  function formatStars(rating) {
    const stars = [];
    for (let i = 0; i < 5; i += 1) {
      if (i < Math.floor(rating)) {
        stars.push("fas fa-star");
      } else if (i < rating) {
        stars.push("fas fa-star-half-alt");
      } else {
        stars.push("far fa-star");
      }
    }
    return stars.map(function (iconClass) {
      return '<i class="' + iconClass + '"></i>';
    }).join("");
  }

  function renderPromoCountdown(course) {
    if (!course.promoActive || !course.promoEndAt) {
      return "";
    }
    return '<span class="inline-flex items-center whitespace-nowrap rounded-full bg-red-600 px-2 py-1 text-[11px] font-semibold text-white shadow-sm dark:bg-red-500 dark:text-white" data-promo-countdown="'
      + escapeHtml(course.promoEndAt)
      + '">Fin dans --</span>';
  }

  function formatCountLabel(count, singular, plural) {
    const value = Number(count || 0);
    return value.toLocaleString("fr-FR") + " " + (value === 1 ? singular : plural);
  }

  function getCartCount() {
    return coursesDB.reduce(function (count, course) {
      return count + (isCourseInCart(course) ? 1 : 0);
    }, 0);
  }

  function formatPromoCountdown(endAt) {
    const endTs = Date.parse(endAt || "");
    if (!Number.isFinite(endTs)) {
      return "Fin bientôt";
    }

    const remainingMs = endTs - Date.now();
    if (remainingMs <= 0) {
      return "Promotion terminée";
    }

    const totalMinutes = Math.floor(remainingMs / 60000);
    const days = Math.floor(totalMinutes / 1440);
    const hours = Math.floor((totalMinutes % 1440) / 60);
    const minutes = totalMinutes % 60;

    if (days > 0) {
      return "Fin dans " + days + " j " + hours + " h";
    }
    if (hours > 0) {
      return "Fin dans " + hours + " h " + minutes + " min";
    }
    return "Fin dans " + Math.max(1, minutes) + " min";
  }

  function refreshPromoCountdowns() {
    document.querySelectorAll("[data-promo-countdown]").forEach(function (node) {
      node.textContent = formatPromoCountdown(node.getAttribute("data-promo-countdown") || "");
    });
  }

  function ensureCatalogToastStack() {
    let stack = document.querySelector("[data-catalog-toast-stack]");
    if (stack) {
      return stack;
    }
    if (!document.body) {
      return null;
    }

    stack = document.createElement("div");
    stack.setAttribute("data-catalog-toast-stack", "1");
    stack.style.position = "fixed";
    stack.style.top = "5.5rem";
    stack.style.right = "1rem";
    stack.style.zIndex = "70";
    stack.style.display = "flex";
    stack.style.flexDirection = "column";
    stack.style.alignItems = "stretch";
    stack.style.gap = "0.75rem";
    stack.style.width = "min(24rem, calc(100vw - 1.5rem))";
    stack.style.pointerEvents = "none";
    document.body.appendChild(stack);
    return stack;
  }

  function showCatalogToast(message, type, url) {
    const text = String(message || "").trim();
    if (!text) {
      return;
    }

    if (typeof window.appShowToast === "function") {
      window.appShowToast(text, type || "info", url || "");
      return;
    }
    if (typeof window.showToast === "function") {
      window.showToast(text, type || "info", url || "");
      return;
    }

    const stack = ensureCatalogToastStack();
    if (!stack) {
      return;
    }

    const normalizedType = type === "success" || type === "error" || type === "warning" ? type : "info";
    const themes = {
      success: {
        background: "linear-gradient(135deg, rgba(6, 78, 59, 0.96), rgba(4, 54, 41, 0.96))",
        border: "rgba(52, 211, 153, 0.38)",
        color: "#ecfdf5",
        accent: "#34d399",
        shadow: "0 22px 48px rgba(2, 6, 23, 0.42)"
      },
      error: {
        background: "linear-gradient(135deg, rgba(127, 29, 29, 0.96), rgba(69, 10, 10, 0.96))",
        border: "rgba(248, 113, 113, 0.42)",
        color: "#fff1f2",
        accent: "#fb7185",
        shadow: "0 22px 48px rgba(2, 6, 23, 0.48)"
      },
      warning: {
        background: "linear-gradient(135deg, rgba(146, 64, 14, 0.96), rgba(69, 26, 3, 0.96))",
        border: "rgba(245, 158, 11, 0.42)",
        color: "#fffbeb",
        accent: "#f59e0b",
        shadow: "0 22px 48px rgba(2, 6, 23, 0.42)"
      },
      info: {
        background: "linear-gradient(135deg, #eff6ff, #dbeafe)",
        border: "#93c5fd",
        color: "#1e3a8a",
        accent: "#2563eb",
        shadow: "0 18px 38px rgba(15, 23, 42, 0.12)"
      }
    };
    const theme = themes[normalizedType] || themes.info;

    const toast = document.createElement("section");
    toast.setAttribute("role", normalizedType === "error" ? "alert" : "status");
    toast.setAttribute("aria-live", normalizedType === "error" ? "assertive" : "polite");
    toast.style.pointerEvents = "auto";
    toast.style.display = "flex";
    toast.style.alignItems = "flex-start";
    toast.style.justifyContent = "space-between";
    toast.style.gap = "0.75rem";
    toast.style.width = "100%";
    toast.style.border = "1px solid " + theme.border;
    toast.style.borderLeft = "4px solid " + theme.accent;
    toast.style.borderRadius = "15px";
    toast.style.background = theme.background;
    toast.style.color = theme.color;
    toast.style.padding = "14px 16px";
    toast.style.boxShadow = theme.shadow;
    toast.style.opacity = "0";
    toast.style.transform = "translateY(-10px) scale(0.98)";
    toast.style.transition = "opacity 180ms ease, transform 180ms ease";

    const content = document.createElement("div");
    content.style.display = "flex";
    content.style.alignItems = "flex-start";
    content.style.gap = "0.75rem";
    content.style.minWidth = "0";

    const icon = document.createElement("span");
    icon.setAttribute("aria-hidden", "true");
    icon.style.display = "inline-flex";
    icon.style.height = "2rem";
    icon.style.width = "2rem";
    icon.style.flex = "0 0 auto";
    icon.style.alignItems = "center";
    icon.style.justifyContent = "center";
    icon.style.borderRadius = "9999px";
    icon.style.background = normalizedType === "success" ? "rgba(52, 211, 153, 0.18)" : normalizedType === "error" ? "rgba(251, 113, 133, 0.16)" : normalizedType === "warning" ? "rgba(245, 158, 11, 0.16)" : "rgba(37, 99, 235, 0.14)";
    icon.style.color = theme.accent;
    icon.innerHTML = '<i class="fas ' + (normalizedType === "success" ? "fa-circle-check" : normalizedType === "error" ? "fa-circle-exclamation" : normalizedType === "warning" ? "fa-triangle-exclamation" : "fa-circle-info") + '" aria-hidden="true"></i>';

    const textWrap = document.createElement("div");
    textWrap.style.minWidth = "0";

    const title = document.createElement("p");
    title.textContent = normalizedType === "success" ? "Succès" : normalizedType === "error" ? "Erreur" : normalizedType === "warning" ? "Attention" : "Information";
    title.style.margin = "0";
    title.style.fontSize = "0.72rem";
    title.style.fontWeight = "800";
    title.style.letterSpacing = "0.14em";
    title.style.textTransform = "uppercase";
    title.style.opacity = "0.75";

    const messageNode = document.createElement("p");
    messageNode.textContent = text;
    messageNode.style.margin = "0.2rem 0 0";
    messageNode.style.fontSize = "0.92rem";
    messageNode.style.fontWeight = "600";
    messageNode.style.lineHeight = "1.45";
    messageNode.style.wordBreak = "break-word";

    textWrap.appendChild(title);
    textWrap.appendChild(messageNode);
    content.appendChild(icon);
    content.appendChild(textWrap);
    toast.appendChild(content);

    const closeButton = document.createElement("button");
    closeButton.type = "button";
    closeButton.setAttribute("aria-label", "Fermer la notification");
    closeButton.textContent = "×";
    closeButton.style.flex = "0 0 auto";
    closeButton.style.border = "1px solid rgba(255,255,255,0.2)";
    closeButton.style.background = "rgba(255,255,255,0.08)";
    closeButton.style.color = theme.color;
    closeButton.style.borderRadius = "9999px";
    closeButton.style.width = "1.75rem";
    closeButton.style.height = "1.75rem";
    closeButton.style.cursor = "pointer";
    closeButton.style.fontSize = "1rem";
    closeButton.style.lineHeight = "1";

    toast.appendChild(closeButton);
    stack.appendChild(toast);

    let closed = false;
    const closeToast = function () {
      if (closed) {
        return;
      }
      closed = true;
      toast.style.opacity = "0";
      toast.style.transform = "translateY(-10px) scale(0.98)";
      window.setTimeout(function () {
        if (toast.parentNode) {
          toast.parentNode.removeChild(toast);
        }
      }, 190);
    };

    closeButton.addEventListener("click", closeToast);

    window.setTimeout(function () {
      requestAnimationFrame(function () {
        toast.style.opacity = "1";
        toast.style.transform = "translateY(0) scale(1)";
      });
    }, 0);

    window.setTimeout(closeToast, 3600);
  }

  function renderBadge(course) {
    const tags = Array.isArray(course.tags) ? course.tags : [];
    const enrolledCount = Number(course.enrolledCount || course.students || 0);
    let label = "";
    let meta = "";

    if (tags.includes("best-seller")) {
      label = "Populaire";
      meta = enrolledCount > 0 ? formatCountLabel(enrolledCount, "inscrit", "inscrits") : "Preuve sociale";
    } else if (tags.includes("top-rated")) {
      label = "Tendance";
    } else if (tags.includes("nouveaute")) {
      label = "Nouveau";
    }

    if (!label) {
      return "";
    }

    return '<span class="catalog-course-badge">'
      + '<span>'
      + escapeHtml(label)
      + '</span>'
      + (meta
        ? '<span class="catalog-course-badge__meta">'
          + escapeHtml(meta)
          + '</span>'
        : "")
      + "</span>";
  }

  function resolveCourseTrust(course) {
    const ratingCount = Number(course.ratingCount || 0);
    const ratingValue = Number(course.rating || 0);
    const enrolledCount = Number(course.enrolledCount || course.students || 0);
    const createdTs = Number(course.createdTs || 0);
    const recentThreshold = Date.now() - (45 * 24 * 60 * 60 * 1000);

    if (ratingCount > 0) {
      return {
        mode: "rated",
        label: ratingValue.toFixed(1),
        meta: formatCountLabel(ratingCount, "avis", "avis"),
        icon: "fa-star",
        iconClass: "text-yellow-400"
      };
    }

    if (createdTs > 0 && (createdTs * 1000) >= recentThreshold && enrolledCount === 0) {
      return {
        mode: "recent",
        label: "Nouveau cours",
        meta: "Disponible maintenant",
        icon: "fa-sparkles",
        iconClass: "text-emerald-500"
      };
    }

    if (createdTs > 0 && (createdTs * 1000) >= recentThreshold && enrolledCount > 0) {
      return {
        mode: "recent-social",
        label: "Formation récemment ajoutée",
        meta: formatCountLabel(enrolledCount, "apprenant", "apprenants"),
        icon: "fa-wand-magic-sparkles",
        iconClass: "text-emerald-500"
      };
    }

    if (enrolledCount > 0) {
      return {
        mode: "available",
        label: "Disponible maintenant",
        meta: formatCountLabel(enrolledCount, "apprenant", "apprenants"),
        icon: "fa-users",
        iconClass: "text-blue-500"
      };
    }

    return {
      mode: "available",
      label: "Disponible maintenant",
      meta: "Inscription ouverte",
      icon: "fa-bolt",
      iconClass: "text-blue-500"
    };
  }

  function renderPricingSection(course) {
    const meta = [];
    const basePriceLabel = String(course.basePriceLabel || "");
    const priceLabel = String(course.priceLabel || "Gratuit");
    const promoLabel = String(course.promoDiscountLabel || "Promotion");
    const hasPromo = !!course.promoActive && ((basePriceLabel && basePriceLabel !== priceLabel) || !!course.promoEndAt || !!course.promoDiscountLabel);
    const hasCoupon = !hasPromo && !!course.couponAvailable;
    const priceHint = String(course.priceHint || "Accès immédiat");
    const priceClass = hasPromo
      ? "whitespace-nowrap text-base font-bold app-price app-price--promo"
      : "whitespace-nowrap text-base font-bold app-price";

    if (hasPromo) {
      meta.push(
        '<span class="inline-flex shrink-0 items-center whitespace-nowrap rounded-full bg-rose-100 px-1.5 py-0.5 text-[10px] font-semibold text-rose-600 dark:bg-rose-500/15 dark:text-rose-200">'
        + escapeHtml(promoLabel)
        + "</span>"
      );
      if (course.promoEndAt) {
        meta.push(
          '<span class="inline-flex shrink-0 items-center whitespace-nowrap rounded-full bg-red-600 px-1.5 py-0.5 text-[10px] font-semibold text-white shadow-sm dark:bg-red-500 dark:text-white" data-promo-countdown="'
          + escapeHtml(course.promoEndAt)
          + '">Fin dans --</span>'
        );
      }
    } else if (hasCoupon) {
      meta.push(
        '<span class="inline-flex items-center whitespace-nowrap rounded-full bg-amber-100 px-1.5 py-0.5 text-[10px] font-semibold text-amber-700 dark:bg-amber-500/15 dark:text-amber-200">Code promo dispo</span>'
      );
    }

    return '<div class="mb-3 min-h-[4.3rem] rounded-lg border border-gray-200 bg-slate-50/90 px-3 py-2 dark:border-gray-800 dark:bg-gray-950/70">'
      + '<div class="flex items-start justify-between gap-3">'
      + '<div class="min-w-0">'
      + '<p class="text-[9px] font-semibold uppercase tracking-[0.16em] text-gray-400 dark:text-gray-500">Prix</p>'
      + '<div class="mt-0.5 flex flex-wrap items-baseline gap-1.5">'
      + '<div class="' + priceClass + '">' + escapeHtml(course.priceLabel || "Gratuit") + "</div>"
      + (basePriceLabel
        ? '<div class="whitespace-nowrap text-[11px] font-medium text-gray-400 line-through' + (hasPromo ? " text-rose-400 dark:text-rose-200/80" : "") + '">' + escapeHtml(basePriceLabel) + "</div>"
        : "")
      + "</div>"
      + '<p class="mt-0.5 text-[10px] font-medium text-gray-500 dark:text-gray-400">' + escapeHtml(priceHint) + "</p>"
      + "</div>"
      + (meta.length > 0 ? '<div class="flex shrink-0 flex-wrap items-center justify-end gap-1.5">' + meta.join("") + "</div>" : "")
      + "</div>"
      + "</div>";
  }

  function isCourseInCart(course) {
    return Number(course && course.isInCart ? course.isInCart : 0) > 0;
  }

  function isCourseFavorite(course) {
    return Number(course && course.isFavorite ? course.isFavorite : 0) > 0;
  }

  function renderActionIcon(action, active) {
    if (action === "favorite") {
      return '<i class="' + (active ? "fas" : "far") + ' fa-heart" aria-hidden="true"></i>';
    }
    return '<i class="fas fa-cart-shopping" aria-hidden="true"></i>';
  }

  function renderActionButton(action, course) {
    const active = action === "favorite" ? isCourseFavorite(course) : isCourseInCart(course);
    const actionClass = action === "favorite" ? "catalog-card__favorite" : "catalog-card__cart";
    const positionClass = action === "favorite" ? " absolute right-3 top-3 z-10" : "";
    const label = action === "favorite"
      ? (active ? "Retirer des favoris" : "Ajouter aux favoris")
      : (active ? "Retirer du panier" : "Ajouter au panier");

    return '<button type="button" class="'
      + actionClass
      + positionClass
      + (active ? " is-active" : "")
      + '" data-course-action="'
      + escapeHtml(action)
      + '" data-course-id="'
      + escapeHtml(course.id)
      + '" aria-label="'
      + escapeHtml(label)
      + '" aria-pressed="'
      + (active ? "true" : "false")
      + '">'
      + renderActionIcon(action, active)
      + "</button>";
  }

  function renderCoursePreview(course) {
    const formatLabel = String(course.formatLabel || "").trim();
    const formatIcon = String(course.formatIcon || "fa-video").trim() || "fa-video";
    const objectiveSnippet = String(course.objectiveSnippet || "").trim();
    const prerequisiteSnippet = String(course.prerequisiteSnippet || "").trim();
    const descriptionPreview = String(course.description || "").trim();
    const primary = objectiveSnippet || descriptionPreview;
    const secondary = prerequisiteSnippet;

    return '<div class="catalog-card__hover-preview">'
      + '<div class="flex flex-wrap gap-1.5">'
      + (formatLabel
        ? '<span class="inline-flex items-center gap-1 rounded-full bg-white/10 px-2 py-1 text-[10px] font-semibold backdrop-blur-sm"><i class="fas '
          + escapeHtml(formatIcon)
          + '" aria-hidden="true"></i>'
          + escapeHtml(formatLabel)
          + '</span>'
        : "")
      + '<span class="inline-flex items-center gap-1 rounded-full bg-white/10 px-2 py-1 text-[10px] font-semibold backdrop-blur-sm"><i class="fas fa-signal" aria-hidden="true"></i>'
      + escapeHtml(course.levelLabel || "Tous niveaux")
      + '</span>'
      + '</div>'
      + (primary
        ? '<p class="line-clamp-2 text-sm font-semibold leading-5">' + escapeHtml(primary) + '</p>'
        : "")
      + (secondary
        ? '<p class="line-clamp-1 text-xs leading-5 text-white/80"><i class="fas fa-check mr-1 text-emerald-300" aria-hidden="true"></i>' + escapeHtml(secondary) + '</p>'
        : "")
      + '<span class="inline-flex items-center gap-1 text-xs font-semibold text-blue-100">Voir le détail <i class="fas fa-arrow-right" aria-hidden="true"></i></span>'
      + '</div>';
  }

  function renderHoverOverlay(detailsUrl) {
    const safeUrl = escapeHtml(String(detailsUrl || "#"));

    return '<div class="catalog-course-card__hover-overlay">'
      + '<a href="' + safeUrl + '" class="catalog-course-card__hover-button">'
      + '<i class="fas fa-arrow-right" aria-hidden="true"></i>'
      + '<span>Voir détails</span>'
      + '</a>'
      + '</div>';
  }

  function renderLevelBadge(level, label) {
    let toneClasses = "bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-200";
    if (level === "debutant") {
      toneClasses = "bg-blue-100 text-blue-800 dark:bg-blue-500/15 dark:text-blue-200";
    } else if (level === "intermediaire") {
      toneClasses = "bg-cyan-100 text-cyan-800 dark:bg-cyan-500/15 dark:text-cyan-200";
    } else if (level === "avance") {
      toneClasses = "bg-violet-100 text-violet-800 dark:bg-violet-500/15 dark:text-violet-200";
    }

    return '<span class="shrink-0 rounded-full px-2 py-0.5 text-[10px] font-semibold '
      + toneClasses
      + '">'
      + escapeHtml(label || "Tous niveaux")
      + "</span>";
  }

  function resolveBadgeLabel(course) {
    if (Array.isArray(course.tags) && course.tags.includes("best-seller")) {
      return "Populaire";
    }
    if (Array.isArray(course.tags) && course.tags.includes("top-rated")) {
      return "Tendance";
    }
    if (Array.isArray(course.tags) && course.tags.includes("nouveaute")) {
      return "Nouveau";
    }
    return "";
  }

  function buildFeaturedProofs(course) {
    const proofs = [];
    proofs.push({ icon: "fa-signal", label: course.levelLabel || "Tous niveaux" });
    proofs.push({ icon: "fa-clock", label: course.durationLabel || "À votre rythme", tone: "duration" });

    const enrolledCount = Number(course.enrolledCount || course.students || 0);
    if (enrolledCount > 0) {
      proofs.push({
        icon: "fa-users",
        label: enrolledCount.toLocaleString("fr-FR") + " apprenants"
      });
    } else if (Number(course.ratingCount || 0) >= 5) {
      proofs.push({
        icon: "fa-star",
        label: Number(course.rating || 0).toFixed(1) + "/5 sur " + Number(course.ratingCount || 0) + " avis"
      });
    } else if (course.promoActive) {
      proofs.push({ icon: "fa-fire", label: "Offre active maintenant" });
    } else if (Array.isArray(course.tags) && course.tags.includes("nouveaute")) {
      proofs.push({ icon: "fa-wand-magic-sparkles", label: "Mis en ligne récemment" });
    } else {
      proofs.push({ icon: "fa-check-circle", label: "Parcours pratique" });
    }

    return proofs;
  }

  function renderCourseCard(course) {
    const pricingSectionHtml = renderPricingSection(course);
    const ratingCount = Number(course.ratingCount || 0);
    const ratingValue = Number(course.rating || 0);
    const enrolledCount = Number(course.enrolledCount || course.students || 0);
    const trust = resolveCourseTrust(course);
    const ctaLabel = "Voir le cours";
    const ctaIconClass = "fa-arrow-right";
    const durationMetaHtml = course.durationLabel
      ? '<span class="catalog-course-duration-badge"><i class="fas fa-clock" aria-hidden="true"></i><span>'
        + escapeHtml(course.durationLabel)
        + "</span></span>"
      : "";
    const ratingLineHtml = '<div class="mb-3 flex min-h-[2.35rem] flex-wrap items-center gap-2 text-xs text-gray-500 dark:text-gray-300">'
      + (ratingCount > 0
        ? '<span class="inline-flex items-center gap-1 text-yellow-500">'
          + '<i class="fas fa-star" aria-hidden="true"></i>'
          + '<span class="font-semibold text-gray-700 dark:text-gray-200">'
          + escapeHtml(ratingValue.toFixed(1))
          + '</span></span>'
          + '<span class="text-gray-400">·</span>'
          + '<span>'
          + escapeHtml(String(ratingCount))
          + ' avis</span>'
        : '<span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-2 py-1 font-semibold text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-200">'
          + '<i class="fas fa-sparkles" aria-hidden="true"></i><span>Nouveau</span></span>'
          + (enrolledCount > 0
            ? '<span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-1 font-semibold text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-200">'
              + '<i class="fas fa-users text-[10px]" aria-hidden="true"></i><span>'
              + escapeHtml(formatCountLabel(enrolledCount, "inscrit", "inscrits"))
              + '</span></span>'
            : '<span class="inline-flex items-center gap-1 text-gray-500 dark:text-gray-300">'
              + '<i class="fas fa-bolt text-[10px]" aria-hidden="true"></i><span>'
              + escapeHtml(trust.meta || "Disponible maintenant")
              + '</span></span>'))
      + (durationMetaHtml ? durationMetaHtml : "")
      + '</div>';
    const detailsUrl = course.url || "#";
    const favoriteButton = renderActionButton("favorite", course);
    const cartButton = renderActionButton("cart", course);
    const categoryLabel = String(course.category || "Formation");

    return '<article class="catalog-course-card hover-lift group flex h-full flex-col overflow-hidden rounded-xl border border-gray-200 bg-white shadow-[0_18px_34px_-24px_rgba(15,23,42,0.28)] dark:border-gray-800 dark:bg-gray-900">'
      + '<div class="catalog-card__cover relative overflow-hidden">'
      + '<a href="' + escapeHtml(detailsUrl) + '" class="block h-full w-full">'
      + '<img src="' + escapeHtml(course.image || "") + '" alt="' + escapeHtml(course.title || "Cours") + '" class="catalog-card__cover-img transition duration-500 group-hover:scale-105" loading="lazy" decoding="async">'
      + "</a>"
      + (categoryLabel ? '<span class="catalog-course-category-badge">' + escapeHtml(categoryLabel) + '</span>' : "")
      + renderBadge(course)
      + favoriteButton
      + "</div>"
      + '<div class="flex flex-1 flex-col p-4">'
      + '<div class="mb-3 flex min-h-[5.45rem] items-start justify-between gap-3">'
      + '<div class="min-w-0 flex-1">'
      + '<h3 class="line-clamp-2 min-h-[2.5rem] text-base font-bold leading-tight text-gray-900 dark:text-white"><a href="' + escapeHtml(detailsUrl) + '" class="transition hover:text-blue-600 dark:hover:text-blue-300">' + escapeHtml(course.title || "Cours") + "</a></h3>"
      + '<div class="mt-2 flex flex-wrap items-center gap-2">'
      + '<div class="inline-flex max-w-full items-center gap-1.5 rounded-full bg-gradient-to-r from-blue-50 to-cyan-50 px-2.5 py-1 text-[11px] font-semibold text-blue-700 dark:from-blue-500/10 dark:to-cyan-500/10 dark:text-blue-200"><i class="fas fa-chalkboard-teacher text-[10px]" aria-hidden="true"></i><span class="truncate">' + escapeHtml(course.teacher || "Formateur") + "</span></div>"
      + "</div>"
      + "</div>"
      + renderLevelBadge(course.level, course.levelLabel)
      + "</div>"
      + ratingLineHtml
      + pricingSectionHtml
      + '<div class="mt-auto flex min-h-[2.75rem] items-center justify-end gap-2 pt-1">'
      + cartButton
      + '<a href="' + escapeHtml(detailsUrl) + '" class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700" title="' + escapeHtml(ctaLabel) + '" aria-label="' + escapeHtml(ctaLabel) + '">'
      + '<span>' + escapeHtml(ctaLabel) + "</span>"
      + '<i class="fas ' + ctaIconClass + '" aria-hidden="true"></i></a>'
      + "</div>"
      + "</div>"
      + renderHoverOverlay(detailsUrl)
      + "</article>";
  }

  function renderCompactCourseCard(course) {
    const ratingCount = Number(course.ratingCount || 0);
    const ratingValue = Number(course.rating || 0);
    const detailsUrl = String(course.url || "#");
    const priceRaw = Number(course.price || 0);
    const inCart = isCourseInCart(course);
    const ctaLabel = priceRaw > 0 ? (inCart ? "Accéder au panier" : "Ajouter au panier") : "Commencer";
    const ctaTone = priceRaw > 0 ? (inCart ? "success" : "primary") : "primary";
    const ctaIconClass = priceRaw > 0 ? (inCart ? "fa-arrow-right" : "fa-cart-shopping") : "fa-circle-play";
    const teacherLabel = String(course.teacher || "Formateur");
    const title = String(course.title || "Cours");
    const categoryLabel = String(course.category || "Formation");
    const priceLabel = String(course.priceLabel || (priceRaw > 0 ? Number(priceRaw).toLocaleString("fr-FR") + " FCFA" : "Gratuit"));
    const basePriceLabel = String(course.basePriceLabel || "");
    const priceHint = String(course.priceHint || "Accès immédiat");
    const durationLabel = String(course.durationLabel || "");
    const enrolledCount = Number(course.enrolledCount || course.students || 0);
    const promoLabel = String(course.promoDiscountLabel || "Promotion");
    const hasPromo = priceRaw > 0 && !!course.promoActive && ((basePriceLabel && basePriceLabel !== priceLabel) || !!course.promoEndAt || !!course.promoDiscountLabel);
    const hasCoupon = !hasPromo && !!course.couponAvailable;
    const priceClass = hasPromo
      ? "catalog-course-card__price app-price app-price--promo"
      : "catalog-course-card__price app-price";
    const favoriteActive = isCourseFavorite(course);
    const favoriteLabel = favoriteActive ? "Retirer des favoris" : "Ajouter aux favoris";
    const favoriteButton = '<button type="button" class="catalog-course-card__favorite' + (favoriteActive ? " is-active" : "") + '" data-course-action="favorite" data-course-id="' + escapeHtml(String(course.id || "")) + '" aria-label="' + escapeHtml(favoriteLabel) + '" aria-pressed="' + (favoriteActive ? "true" : "false") + '">'
      + renderActionIcon("favorite", favoriteActive)
      + "</button>";
    const starsHtml = ratingCount > 0
      ? formatStars(ratingValue)
      : '<i class="far fa-star"></i><i class="far fa-star"></i><i class="far fa-star"></i><i class="far fa-star"></i><i class="far fa-star"></i>';

    return '<article class="catalog-course-card hover-lift group flex h-full flex-col">'
      + '<div class="catalog-course-card__media">'
      + '<a href="' + escapeHtml(detailsUrl) + '" class="catalog-course-card__media-link">'
      + '<img src="' + escapeHtml(String(course.image || "")) + '" alt="' + escapeHtml(title) + '" class="catalog-course-card__media-img" loading="lazy" decoding="async">'
      + "</a>"
      + (categoryLabel
        ? '<span class="catalog-course-category-badge">' + escapeHtml(categoryLabel) + '</span>'
        : "")
      + (durationLabel
        ? '<div class="pointer-events-none absolute inset-x-0 bottom-0 bg-gradient-to-t from-slate-950/55 via-slate-950/5 to-transparent px-3 pb-3 pt-8"><div class="flex justify-end"><span class="catalog-course-duration-badge"><i class="far fa-clock text-[10px]" aria-hidden="true"></i>' + escapeHtml(durationLabel) + "</span></div></div>"
        : "")
      + renderHoverOverlay(detailsUrl)
      + "</div>"
      + '<div class="catalog-course-card__body">'
      + '<div class="mb-3 flex min-h-[5.45rem] items-start justify-between gap-3">'
      + '<div class="min-w-0 flex-1">'
      + '<div class="catalog-course-card__author">'
      + '<span class="catalog-course-card__author-icon" aria-hidden="true"><i class="fas fa-chalkboard-teacher text-xs" aria-hidden="true"></i></span>'
      + '<span class="catalog-course-card__author-name" title="' + escapeHtml(teacherLabel) + '">' + escapeHtml(teacherLabel) + "</span>"
      + "</div>"
      + '<h3 class="catalog-course-card__title"><a href="' + escapeHtml(detailsUrl) + '" class="catalog-course-card__title-link">' + escapeHtml(title) + "</a></h3>"
      + "</div>"
      + '<div class="flex shrink-0 flex-col items-end gap-2">'
      + favoriteButton
      + renderLevelBadge(course.level, course.levelLabel)
      + "</div>"
      + "</div>"
      + '<div class="catalog-course-card__rating">'
      + (ratingCount > 0
        ? '<span class="catalog-course-card__rating-stars" aria-hidden="true">' + starsHtml + '</span><span class="catalog-course-card__rating-value">' + escapeHtml(ratingValue.toFixed(1)) + '</span><span>' + escapeHtml(String(ratingCount)) + ' Avis</span>'
        : '<span class="catalog-course-card__rating-stars" aria-hidden="true">' + starsHtml + '</span>'
          + '<span class="inline-flex items-center gap-1 whitespace-nowrap rounded-full bg-emerald-100 px-2 py-1 text-[0.72rem] font-semibold text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-200">'
          + '<i class="fas fa-sparkles" aria-hidden="true"></i><span>Nouveau</span></span>'
          + (enrolledCount > 0
            ? '<span class="inline-flex items-center gap-1 whitespace-nowrap rounded-full bg-emerald-50 px-2 py-1 text-[0.72rem] font-semibold text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-200">'
              + '<i class="fas fa-users text-[10px]" aria-hidden="true"></i><span>'
              + escapeHtml(formatCountLabel(enrolledCount, "inscrit", "inscrits"))
              + '</span></span>'
            : ""))
      + "</div>"
      + '<div class="catalog-course-card__footer">'
      + '<div class="catalog-course-card__pricing">'
      + '<span class="catalog-course-card__price-label">Prix</span>'
      + '<strong class="' + priceClass + '">' + escapeHtml(priceLabel) + "</strong>"
      + (basePriceLabel && basePriceLabel !== priceLabel ? '<span class="catalog-course-card__base-price">' + escapeHtml(basePriceLabel) + "</span>" : "")
      + '<span class="text-[0.78rem] font-medium text-slate-500 dark:text-slate-400">' + escapeHtml(priceHint || "Accès immédiat") + "</span>"
      + ((hasPromo || hasCoupon)
        ? '<div class="mt-1 flex flex-wrap items-center gap-1.5">'
          + (hasPromo
            ? '<span class="inline-flex items-center whitespace-nowrap rounded-full bg-rose-100 px-1.5 py-0.5 text-[10px] font-semibold text-rose-600 dark:bg-rose-500/15 dark:text-rose-200">' + escapeHtml(promoLabel) + '</span>'
              + (course.promoEndAt
                ? '<span class="inline-flex items-center whitespace-nowrap rounded-full bg-red-600 px-1.5 py-0.5 text-[10px] font-semibold text-white shadow-sm dark:bg-red-500 dark:text-white" data-promo-countdown="' + escapeHtml(course.promoEndAt) + '">Fin dans --</span>'
                : "")
            : '<span class="inline-flex items-center whitespace-nowrap rounded-full bg-amber-100 px-1.5 py-0.5 text-[10px] font-semibold text-amber-700 dark:bg-amber-500/15 dark:text-amber-200">Code promo dispo</span>')
          + '</div>'
        : "")
      + "</div>"
      + (priceRaw > 0
        ? (inCart
          ? '<a href="' + escapeHtml(checkoutUrl) + '" class="catalog-course-card__cta catalog-course-card__cta--' + ctaTone + '"><i class="fas ' + ctaIconClass + '" aria-hidden="true"></i><span>' + escapeHtml(ctaLabel) + '</span></a>'
          : '<button type="button" class="catalog-course-card__cta catalog-course-card__cta--' + ctaTone + '" data-course-action="cart" data-course-id="' + escapeHtml(String(course.id || "")) + '" aria-label="' + escapeHtml(ctaLabel) + '" aria-pressed="false"><i class="fas ' + ctaIconClass + '" aria-hidden="true"></i><span>' + escapeHtml(ctaLabel) + '</span></button>')
        : '<a href="' + escapeHtml(detailsUrl) + '" class="catalog-course-card__cta catalog-course-card__cta--' + ctaTone + '"><i class="fas ' + ctaIconClass + '" aria-hidden="true"></i><span>' + escapeHtml(ctaLabel) + '</span></a>')
      + "</div>"
      + "</div>"
      + "</article>";
  }

  const objectivesNode = document.getElementById("catalog-objectives-data");
  const dataNode = document.getElementById("catalog-courses-data");
  const grid = document.getElementById("coursesGrid");
  const resultCount = document.getElementById("resultCount");
  const searchInput = document.getElementById("searchInput");
  const searchSubmitButton = document.getElementById("searchSubmitButton");
  const searchClearButton = document.getElementById("searchClearButton");
  const sortSelect = document.getElementById("sortSelect");
  const ratingSlider = document.getElementById("ratingSlider");
  const ratingSpan = document.getElementById("ratingValue");
  const ratingStars = Array.from(document.querySelectorAll("[data-rating-star]"));
  const resetBtn = document.getElementById("resetFiltersBtn");
  const paginationNav = document.getElementById("paginationNav");
  const filterContainer = document.getElementById("filterContainer");
  const filterPanel = document.getElementById("catalogFilterPanel");
  const filterToggleButtons = Array.from(document.querySelectorAll("[data-filter-toggle]"));
  const filterCloseButtons = Array.from(document.querySelectorAll("[data-filter-close]"));
  const chipsRow = document.getElementById("activeFiltersChips");
  const quickFilterButtons = Array.from(document.querySelectorAll("[data-quick-filter]"));
  const categoryCheckboxes = Array.from(document.querySelectorAll(".category-filter"));
  const levelInputs = Array.from(document.querySelectorAll(".level-filter"));
  const priceRadios = Array.from(document.querySelectorAll(".price-radio"));
  const minPriceInput = document.getElementById("minPriceInput");
  const maxPriceInput = document.getElementById("maxPriceInput");
  const shortcutButtons = Array.from(document.querySelectorAll("[data-catalog-shortcut]"));
  const body = document.body;
  const compactCatalogFilters = body ? body.dataset.compactFilters === "1" : false;
  const csrfToken = (document.querySelector('meta[name="csrf-token"]') || {}).content || "";
  const cartApiUrl = body ? (body.dataset.cartApi || "") : "";
  const checkoutUrl = body ? (body.dataset.checkoutUrl || "/checkout") : "/checkout";
  const favoritesApiUrl = body ? (body.dataset.favoritesApi || "") : "";
  const loginUrl = body ? (body.dataset.loginUrl || "") : "";
  const isAuthenticated = body ? body.dataset.isAuthenticated === "1" : false;
  const cartCountNodes = Array.from(document.querySelectorAll("[data-catalog-cart-count]"));
  const cartLinkNodes = Array.from(document.querySelectorAll("[data-catalog-cart-link]"));
  const scrollTopButton = document.querySelector("[data-scroll-top]");
  const supportFab = document.querySelector("[data-support-fab]");
  const featuredImage = document.getElementById("featuredRecommendationImage");
  const featuredEyebrow = document.getElementById("featuredRecommendationEyebrow");
  const featuredTitle = document.getElementById("featuredRecommendationTitle");
  const featuredSupport = document.getElementById("featuredRecommendationSupport");
  const featuredDescription = document.getElementById("featuredRecommendationDescription");
  const featuredTeacher = document.getElementById("featuredRecommendationTeacher");
  const featuredProofs = document.getElementById("featuredRecommendationProofs");
  const featuredPrice = document.getElementById("featuredRecommendationPrice");
  const featuredBasePrice = document.getElementById("featuredRecommendationBasePrice");
  const featuredPrimary = document.getElementById("featuredRecommendationPrimary");
  const featuredSecondary = document.getElementById("featuredRecommendationSecondary");

  if (!dataNode || !grid || !resultCount || !searchInput || !sortSelect || !ratingSlider || !ratingSpan || !resetBtn || !paginationNav || !filterContainer || !filterPanel) {
    return;
  }

  let coursesDB = [];
  try {
    coursesDB = JSON.parse(dataNode.textContent || "[]");
  } catch (_error) {
    coursesDB = [];
  }

  let objectiveDB = [];
  try {
    objectiveDB = JSON.parse((objectivesNode && objectivesNode.textContent) || "[]");
  } catch (_error) {
    objectiveDB = [];
  }

  const pageSize = 12;
  let activeCategories = new Set();
  let activeLevels = new Set();
  let priceFilter = "all";
  let minPrice = "";
  let maxPrice = "";
  let minRating = 0;
  let searchQuery = "";
  let currentPage = 1;
  const levelLabelByKey = {
    debutant: "Débutant",
    intermediaire: "Intermédiaire",
    avance: "Avancé"
  };
  const categoryLabelByKey = new Map();
  categoryCheckboxes.forEach(function (checkbox) {
    const label = checkbox.getAttribute("data-label") || "";
    if (label) {
      categoryLabelByKey.set(checkbox.value, label);
    }
  });

  function hasActiveFilters() {
    return String(searchQuery || "").trim() !== ""
      || activeCategories.size > 0
      || activeLevels.size > 0
      || priceFilter !== "all"
      || minPrice !== ""
      || maxPrice !== ""
      || minRating > 0;
  }

  function syncSearchClearButton() {
    if (!(searchClearButton instanceof HTMLElement)) {
      return;
    }
    const hasQuery = String(searchInput.value || "").trim() !== "";
    searchClearButton.classList.toggle("hidden", !hasQuery);
    searchClearButton.classList.toggle("inline-flex", hasQuery);
  }

  function updateQuickFilterStates() {
    quickFilterButtons.forEach(function (button) {
      if (!(button instanceof HTMLElement)) {
        return;
      }

      const type = button.getAttribute("data-quick-filter") || "";
      const value = button.getAttribute("data-filter-value") || "";
      let isActive = false;

      if (type === "all") {
        isActive = !hasActiveFilters();
      } else if (type === "price") {
        isActive = priceFilter === value;
      } else if (type === "category") {
        isActive = activeCategories.has(value);
      } else if (type === "level") {
        isActive = activeLevels.has(value);
      }

      button.setAttribute("aria-pressed", isActive ? "true" : "false");
    });
  }

  function syncFilterInputs() {
    categoryCheckboxes.forEach(function (checkbox) {
      checkbox.checked = activeCategories.has(checkbox.value);
    });
    levelInputs.forEach(function (input) {
      const level = input.getAttribute("data-level") || input.value || "";
      input.checked = activeLevels.has(level);
    });
    priceRadios.forEach(function (radio) {
      radio.checked = radio.value === priceFilter;
    });
  }

  function applyInitialFiltersFromUrl() {
    const params = new URLSearchParams(window.location.search || "");
    const initialQuery = String(params.get("q") || "").trim();
    if (initialQuery !== "") {
      searchInput.value = initialQuery;
      searchQuery = initialQuery;
    }

    const rawCategoryValues = params.getAll("category");
    rawCategoryValues.forEach(function (rawValue) {
      String(rawValue || "")
        .split(",")
        .map(function (value) {
          return value.trim();
        })
        .filter(function (value) {
          return value !== "";
        })
        .forEach(function (value) {
          activeCategories.add(value);
        });
    });

    syncFilterInputs();
    searchQuery = String(searchInput.value || "").trim();
    syncSearchClearButton();
  }

  function applyQuickFilter(button) {
    const type = button.getAttribute("data-quick-filter") || "";
    const value = button.getAttribute("data-filter-value") || "";

    if (type === "all") {
      resetFilters();
      return;
    }

    if (type === "price") {
      priceFilter = priceFilter === value ? "all" : value;
    } else if (type === "category" && value !== "") {
      if (activeCategories.has(value)) {
        activeCategories.delete(value);
      } else {
        activeCategories.add(value);
      }
    } else if (type === "level" && value !== "") {
      if (activeLevels.has(value)) {
        activeLevels.delete(value);
      } else {
        activeLevels.add(value);
      }
    }

    syncFilterInputs();
    currentPage = 1;
    renderCourses();
  }

  let activeObjectiveKey = objectiveDB.length > 0 && objectiveDB[0] && objectiveDB[0].key
    ? String(objectiveDB[0].key)
    : "";

  function findCourseById(courseId) {
    return coursesDB.find(function (course) {
      return Number(course.id || 0) === Number(courseId || 0);
    }) || null;
  }

  function scoreCourseForObjective(course, objective) {
    const objectiveSearch = normalizeText((objective && objective.search) || "");
    const objectiveCategoryKey = String((objective && objective.categoryKey) || "");
    let score = 0;

    if (objectiveCategoryKey !== "" && String(course.categoryKey || "") === objectiveCategoryKey) {
      score += 100;
    }
    if (objectiveSearch !== "" && normalizeText(course.searchBlob || "").indexOf(objectiveSearch) !== -1) {
      score += 55;
    }
    if (objectiveSearch !== "" && normalizeText(course.title || "").indexOf(objectiveSearch) !== -1) {
      score += 20;
    }
    if (Array.isArray(course.tags) && course.tags.includes("best-seller")) {
      score += 18;
    }
    if (Array.isArray(course.tags) && course.tags.includes("top-rated")) {
      score += 14;
    }
    score += Math.min(20, Number(course.enrolledCount || course.students || 0));
    score += Math.round(Number(course.rating || 0) * 4);
    if (course.promoActive) {
      score += 4;
    }
    return score;
  }

  function findRecommendedCourseForObjective(objective) {
    if (!objective) {
      return coursesDB[0] || null;
    }
    if (objective.featuredCourseId) {
      const matched = findCourseById(objective.featuredCourseId);
      if (matched) {
        return matched;
      }
    }

    let bestCourse = null;
    let bestScore = -1;
    coursesDB.forEach(function (course) {
      const score = scoreCourseForObjective(course, objective);
      if (score > bestScore) {
        bestScore = score;
        bestCourse = course;
      }
    });
    return bestCourse || coursesDB[0] || null;
  }

  function setActiveShortcutState(nextKey) {
    activeObjectiveKey = String(nextKey || "");
    shortcutButtons.forEach(function (button) {
      const isActive = String(button.getAttribute("data-shortcut-key") || "") === activeObjectiveKey;
      button.setAttribute("aria-pressed", isActive ? "true" : "false");
      button.classList.toggle("ring-2", isActive);
      button.classList.toggle("ring-blue-500/70", isActive);
      button.classList.toggle("ring-offset-2", isActive);
      button.classList.toggle("ring-offset-white", isActive);
      button.classList.toggle("dark:ring-blue-400/70", isActive);
      button.classList.toggle("dark:ring-offset-gray-900", isActive);
    });
  }

  function renderFeaturedRecommendation(course, objective) {
    if (!course || !featuredTitle || !featuredEyebrow || !featuredDescription || !featuredTeacher || !featuredProofs || !featuredPrice || !featuredBasePrice || !featuredPrimary || !featuredSecondary) {
      return;
    }

    const proofs = buildFeaturedProofs(course);
    if (featuredImage instanceof HTMLImageElement) {
      featuredImage.src = String(course.image || "");
      featuredImage.alt = String(course.title || "Cours recommandé");
    }
    featuredEyebrow.innerHTML = '<i class="fas fa-wand-magic-sparkles text-amber-300"></i> ' + escapeHtml((objective && objective.featuredEyebrow) || "Recommandé pour vous");
    featuredTitle.textContent = String(course.title || "Cours recommandé");
    if (featuredSupport) {
      featuredSupport.textContent = String((objective && objective.featuredSupport) || "Passez a l action plus vite.");
    }
    featuredDescription.textContent = String(course.description || "");
    featuredTeacher.textContent = String(course.teacher || "Formateur");
    featuredPrice.textContent = String(course.priceLabel || "Gratuit");
    featuredPrice.classList.add("app-price");
    featuredPrice.classList.toggle("app-price--promo", !!course.promoActive);

    if (course.basePriceLabel) {
      featuredBasePrice.textContent = String(course.basePriceLabel);
      featuredBasePrice.classList.remove("hidden");
    } else {
      featuredBasePrice.textContent = "";
      featuredBasePrice.classList.add("hidden");
    }

    featuredPrimary.setAttribute("href", String(course.buyUrl || course.url || "#"));
    featuredPrimary.setAttribute("title", isAuthenticated ? "Acheter" : "Commencer");
    featuredPrimary.setAttribute("aria-label", isAuthenticated ? "Acheter" : "Commencer");
    featuredPrimary.innerHTML = '<i class="fas ' + (isAuthenticated ? "fa-cart-shopping" : "fa-circle-play") + '" aria-hidden="true"></i><span class="sr-only">' + escapeHtml(isAuthenticated ? "Acheter" : "Commencer") + "</span>";
    featuredSecondary.setAttribute("href", String(course.url || "#"));
    featuredSecondary.setAttribute("title", "Voir le cours");
    featuredSecondary.setAttribute("aria-label", "Voir le cours");
    featuredSecondary.innerHTML = '<i class="fas fa-arrow-right" aria-hidden="true"></i><span class="sr-only">Voir le cours</span>';

    const badgeLabel = resolveBadgeLabel(course);
    featuredProofs.innerHTML = (badgeLabel
      ? '<span class="rounded-full px-2.5 py-1 backdrop-blur-sm' + (badgeLabel === "Nouveau" ? ' bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-200' : ' bg-white/10') + '">' + escapeHtml(badgeLabel) + "</span>"
      : "")
      + proofs.map(function (proof) {
        const isDurationProof = proof && proof.tone === "duration";
        return '<span class="inline-flex items-center gap-2 rounded-full px-2.5 py-1 backdrop-blur-sm'
          + (isDurationProof ? ' bg-red-100 text-red-700 dark:bg-red-500/15 dark:text-red-200' : ' bg-white/10')
          + '"><i class="fas '
          + escapeHtml(proof.icon || "fa-circle")
          + (isDurationProof ? ' text-red-600 dark:text-red-200' : ' text-blue-200')
          + '"></i>'
          + escapeHtml(proof.label || "")
          + "</span>";
      }).join("");
  }

  function setFilterToggleState(isOpen) {
    filterToggleButtons.forEach(function (button) {
      if (!(button instanceof HTMLElement)) {
        return;
      }

      const closedLabel = window.innerWidth < 640 ? "Filtres" : (compactCatalogFilters ? "Filtrer les formations" : "Afficher les filtres");
      const label = isOpen ? "Masquer les filtres" : closedLabel;
      const iconClass = isOpen ? "fa-chevron-up" : "fa-chevron-down";
      button.setAttribute("aria-expanded", isOpen ? "true" : "false");
      button.setAttribute("aria-label", label);
      button.innerHTML = '<span><i class="fas fa-filter mr-2 text-blue-600 dark:text-blue-300"></i> ' + label + '</span><i class="fas ' + iconClass + ' text-gray-400"></i>';
    });
  }

  let filtersOpen = window.innerWidth >= 1024 && !compactCatalogFilters;

  function setFilterContainerState(isOpen, rememberState) {
    if (!(filterContainer instanceof HTMLElement)) {
      return;
    }
    const shouldRemember = rememberState !== false;
    if (shouldRemember) {
      filtersOpen = !!isOpen;
    }

    filterContainer.style.display = isOpen ? "" : "none";
    filterContainer.classList.toggle("hidden", !isOpen);

    if (filterPanel instanceof HTMLElement) {
      filterPanel.style.display = isOpen ? "" : "none";
    }
    if (body) {
      body.classList.toggle("catalog-filter-open", !!isOpen && window.innerWidth < 1024);
    }
  }

  function closeMobileFilters() {
    setFilterContainerState(false);
    setFilterToggleState(false);
  }

  function syncRatingStars() {
    ratingStars.forEach(function (button) {
      const starValue = Number(button.getAttribute("data-rating") || 0);
      const icon = button.querySelector("i");
      if (!(icon instanceof HTMLElement)) {
        return;
      }
      const isActive = starValue <= minRating;
      icon.className = (isActive ? "fas" : "far") + " fa-star";
      button.setAttribute("aria-pressed", isActive ? "true" : "false");
    });
  }

  function findCourseIndex(courseId) {
    return coursesDB.findIndex(function (course) {
      return Number(course.id || 0) === Number(courseId || 0);
    });
  }

  function updateCourseState(courseId, patch) {
    const index = findCourseIndex(courseId);
    if (index === -1) {
      return;
    }
    coursesDB[index] = Object.assign({}, coursesDB[index], patch || {});
  }

  function chipHtml(type, key, label) {
    return '<button type="button" class="inline-flex items-center gap-2 rounded-full border border-blue-200 bg-blue-50 px-3 py-1 text-sm font-medium text-blue-700 transition hover:border-blue-300 hover:bg-blue-100 dark:border-blue-500/30 dark:bg-blue-500/10 dark:text-blue-200 dark:hover:border-blue-400" data-chip="'
      + escapeHtml(type)
      + '" data-key="'
      + escapeHtml(key)
      + '"><span>'
      + escapeHtml(label)
      + '</span><i class="fas fa-times text-gray-400"></i></button>';
  }

  function renderActiveChips() {
    if (!chipsRow) {
      return;
    }

    const chips = [];
    const trimmedQuery = String(searchQuery || "").trim();
    if (trimmedQuery !== "") {
      chips.push(chipHtml("search", "", "Recherche: " + trimmedQuery));
    }
    activeCategories.forEach(function (categoryKey) {
      const label = categoryLabelByKey.get(categoryKey) || categoryKey;
      chips.push(chipHtml("category", categoryKey, label));
    });
    activeLevels.forEach(function (levelKey) {
      chips.push(chipHtml("level", levelKey, "Niveau: " + (levelLabelByKey[levelKey] || levelKey)));
    });
    if (priceFilter === "free") {
      chips.push(chipHtml("price", "free", "Prix: Gratuit"));
    } else if (priceFilter === "paid") {
      chips.push(chipHtml("price", "paid", "Prix: Payant"));
    }
    if (minPrice !== "" || maxPrice !== "") {
      chips.push(chipHtml("priceRange", "range", "Prix: " + (minPrice !== "" ? minPrice : "0") + " - " + (maxPrice !== "" ? maxPrice : "max")));
    }
    if (minRating > 0) {
      chips.push(chipHtml("rating", String(minRating), "Note >= " + minRating.toFixed(1)));
    }

    if (chips.length === 0) {
      chipsRow.innerHTML = "";
      chipsRow.classList.add("hidden");
      updateQuickFilterStates();
      syncSearchClearButton();
      return;
    }

    chipsRow.classList.remove("hidden");
    chips.push('<button type="button" class="inline-flex items-center gap-2 rounded-full border border-gray-200 bg-white px-3 py-1 text-sm font-semibold text-gray-700 transition hover:border-rose-300 hover:text-rose-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:hover:border-rose-400 dark:hover:text-rose-200" data-chip="reset" data-key=""><i class="fas fa-rotate-left text-xs" aria-hidden="true"></i><span>Réinitialiser</span></button>');
    chipsRow.innerHTML = chips.join("");
    updateQuickFilterStates();
    syncSearchClearButton();
  }

  function renderPagination(totalItems) {
    const totalPages = Math.max(1, Math.ceil(totalItems / pageSize));
    if (totalItems <= pageSize) {
      paginationNav.innerHTML = "";
      if (paginationNav.parentElement) {
        paginationNav.parentElement.classList.add("hidden");
      }
      return;
    }

    if (paginationNav.parentElement) {
      paginationNav.parentElement.classList.remove("hidden");
    }

    const buttons = [];
    buttons.push(
      '<button type="button" data-page="' + Math.max(1, currentPage - 1) + '"'
      + (currentPage === 1 ? " disabled" : "")
      + ' class="flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 transition hover:border-blue-300 hover:text-blue-600 disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-blue-400 dark:hover:text-blue-300"><i class="fas fa-chevron-left text-xs"></i></button>'
    );

    const visibleItems = [];
    if (totalPages <= 6) {
      for (let page = 1; page <= totalPages; page += 1) {
        visibleItems.push(page);
      }
    } else if (currentPage <= 3) {
      visibleItems.push(1, 2, 3, "...", totalPages);
    } else if (currentPage >= totalPages - 2) {
      visibleItems.push(1, "...", totalPages - 2, totalPages - 1, totalPages);
    } else {
      visibleItems.push(1, "...", currentPage - 1, currentPage, currentPage + 1, "...", totalPages);
    }

    visibleItems.forEach(function (item) {
      if (item === "...") {
        buttons.push('<span class="px-2 text-gray-400">...</span>');
        return;
      }

      const page = Number(item);
      const activeClass = page === currentPage
        ? "bg-blue-600 text-white font-medium shadow-sm"
        : "border border-gray-200 bg-white text-gray-600 transition hover:border-blue-300 hover:text-blue-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-blue-400 dark:hover:text-blue-300";
      buttons.push(
        '<button type="button" data-page="' + page + '" class="flex h-9 w-9 items-center justify-center rounded-lg ' + activeClass + '">' + page + "</button>"
      );
    });

    buttons.push(
      '<button type="button" data-page="' + Math.min(totalPages, currentPage + 1) + '"'
      + (currentPage === totalPages ? " disabled" : "")
      + ' class="flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 transition hover:border-blue-300 hover:text-blue-600 disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-blue-400 dark:hover:text-blue-300"><i class="fas fa-chevron-right text-xs"></i></button>'
    );

    paginationNav.innerHTML = buttons.join("");
  }

  function filterCourses() {
    const normalizedQuery = normalizeText(searchQuery);

    return coursesDB.filter(function (course) {
      if (activeCategories.size > 0 && !activeCategories.has(course.categoryKey)) {
        return false;
      }
      if (activeLevels.size > 0 && !activeLevels.has(course.level)) {
        return false;
      }
      if (priceFilter === "free" && Number(course.price || 0) !== 0) {
        return false;
      }
      if (priceFilter === "paid" && Number(course.price || 0) === 0) {
        return false;
      }
      if (minPrice !== "" && Number(course.price || 0) < Number(minPrice)) {
        return false;
      }
      if (maxPrice !== "" && Number(course.price || 0) > Number(maxPrice)) {
        return false;
      }
      if (Number(course.rating || 0) < minRating) {
        return false;
      }
      if (normalizedQuery !== "" && normalizeText(course.searchBlob).indexOf(normalizedQuery) === -1) {
        return false;
      }
      return true;
    });
  }

  function sortCourses(courses) {
    const sorted = courses.slice();
    const sort = sortSelect.value;

    if (sort === "popular") {
      sorted.sort(function (left, right) {
        return Number(right.enrolledCount || right.students || 0) - Number(left.enrolledCount || left.students || 0)
          || Number(right.rating || 0) - Number(left.rating || 0)
          || Number(right.createdTs || 0) - Number(left.createdTs || 0);
      });
    }
    if (sort === "new") {
      sorted.sort(function (left, right) {
        return Number(right.createdTs || 0) - Number(left.createdTs || 0);
      });
    }
    if (sort === "rating") {
      sorted.sort(function (left, right) {
        return Number(right.rating || 0) - Number(left.rating || 0)
          || Number(right.enrolledCount || right.students || 0) - Number(left.enrolledCount || left.students || 0);
      });
    }
    if (sort === "priceAsc") {
      sorted.sort(function (left, right) {
        return Number(left.price || 0) - Number(right.price || 0)
          || Number(right.rating || 0) - Number(left.rating || 0);
      });
    }

    return sorted;
  }

  function renderCourses() {
    const filtered = sortCourses(filterCourses());
    const totalItems = filtered.length;
    const totalPages = Math.max(1, Math.ceil(totalItems / pageSize));
    if (currentPage > totalPages) {
      currentPage = totalPages;
    }

    const startIndex = (currentPage - 1) * pageSize;
    const visibleCourses = filtered.slice(startIndex, startIndex + pageSize);
    renderActiveChips();

    if (totalItems === 0) {
      grid.innerHTML = '<div class="col-span-full rounded-3xl border border-dashed border-gray-300 bg-white/70 py-16 text-center text-gray-500 dark:border-gray-700 dark:bg-gray-900/60 dark:text-gray-400"><i class="fas fa-search mb-4 text-4xl opacity-40"></i><p>Aucun cours ne correspond à vos critères.</p><button class="mt-3 font-medium text-blue-600 underline dark:text-blue-300" id="emptyResetButton">Réinitialiser les filtres</button></div>';
      resultCount.innerHTML = 'Affichage de <span class="font-semibold text-gray-900 dark:text-white">0</span> cours';
      renderPagination(0);
      const emptyResetButton = document.getElementById("emptyResetButton");
      if (emptyResetButton) {
        emptyResetButton.addEventListener("click", function () {
          resetFilters();
        });
      }
      return;
    }

    grid.innerHTML = visibleCourses.map(renderCompactCourseCard).join("");
    resultCount.innerHTML = 'Affichage de <span class="font-semibold text-gray-900 dark:text-white">' + visibleCourses.length + "</span> cours sur " + totalItems;
    renderPagination(totalItems);
    refreshPromoCountdowns();
  }

  function resetFilters() {
    activeCategories.clear();
    categoryCheckboxes.forEach(function (checkbox) {
      checkbox.checked = false;
    });
    activeLevels.clear();
    levelInputs.forEach(function (input) {
      input.checked = false;
    });
    priceFilter = "all";
    priceRadios.forEach(function (radio) {
      radio.checked = radio.value === "all";
    });
    minPrice = "";
    maxPrice = "";
    if (minPriceInput) {
      minPriceInput.value = "";
    }
    if (maxPriceInput) {
      maxPriceInput.value = "";
    }
    minRating = 0;
    ratingSlider.value = "0";
    ratingSpan.textContent = "0.0";
    syncRatingStars();
    searchQuery = "";
    searchInput.value = "";
    sortSelect.value = "popular";
    currentPage = 1;
    renderCourses();
  }

  function updateCartIndicators(count) {
    const safeCount = count === null || typeof count === "undefined"
      ? getCartCount()
      : Math.max(0, Number(count || 0));
    cartCountNodes.forEach(function (node) {
      node.textContent = String(safeCount);
      node.classList.toggle("hidden", safeCount <= 0);
    });
    cartLinkNodes.forEach(function (node) {
      node.setAttribute("aria-label", safeCount > 0 ? ("Panier, " + safeCount + " cours") : "Panier");
    });
  }

  function updateActionButton(button, action, active) {
    if (!button) {
      return;
    }
    button.classList.toggle("is-active", !!active);
    button.setAttribute("aria-pressed", active ? "true" : "false");
    if (action === "favorite") {
      button.setAttribute("aria-label", active ? "Retirer des favoris" : "Ajouter aux favoris");
      button.innerHTML = renderActionIcon("favorite", active);
      return;
    }
    button.setAttribute("aria-label", active ? "Retirer du panier" : "Ajouter au panier");
    button.innerHTML = renderActionIcon("cart", active);
  }

  function updateVisibleCourseButtons(courseId) {
    const targetId = String(courseId);
    const cartCourse = coursesDB.find(function (course) {
      return String(course.id) === targetId;
    }) || {};
    grid.querySelectorAll('[data-course-id="' + targetId + '"]').forEach(function (button) {
      const action = button.getAttribute("data-course-action") || "";
      if (action === "favorite") {
        updateActionButton(button, "favorite", isCourseFavorite(cartCourse));
      } else if (action === "cart") {
        updateActionButton(button, "cart", isCourseInCart(cartCourse));
      }
      button.classList.remove("is-busy");
      button.disabled = false;
    });
  }

  function buildReturnUrl() {
    return window.location.pathname + window.location.search + window.location.hash;
  }

  function redirectToLogin() {
    if (!loginUrl) {
      return;
    }
    const separator = loginUrl.indexOf("?") === -1 ? "?" : "&";
    window.location.href = loginUrl + separator + "return=" + encodeURIComponent(buildReturnUrl());
  }

  function postForm(url, payload) {
    return fetch(url, {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
        "Accept": "application/json",
        "X-Requested-With": "XMLHttpRequest"
      },
      body: payload.toString(),
      credentials: "same-origin"
    }).then(function (response) {
      return response.json().catch(function () {
        return null;
      }).then(function (data) {
        return { ok: response.ok, status: response.status, data: data };
      });
    });
  }

  function handleCartToggle(button) {
    const courseId = Number(button.getAttribute("data-course-id") || 0);
    const index = findCourseIndex(courseId);
    if (!cartApiUrl || courseId < 1 || index === -1) {
      return;
    }

    const currentlyInCart = isCourseInCart(coursesDB[index]);
    const action = currentlyInCart ? "remove" : "add";
    const payload = new URLSearchParams();
    payload.set("_csrf", csrfToken);
    payload.set("course_id", String(courseId));
    payload.set("origin_block", "catalogue");

    button.classList.add("is-busy");
    button.disabled = true;

    postForm(cartApiUrl + "?action=" + encodeURIComponent(action), payload)
      .then(function (result) {
        if (!result || !result.ok || !result.data) {
          throw new Error("cart_request_failed");
        }
        updateCourseState(courseId, { isInCart: action === "add" ? 1 : 0 });
        updateCartIndicators(Object.prototype.hasOwnProperty.call(result.data, "count") ? result.data.count : null);
        renderCourses();
        showCatalogToast(action === "add" ? "Formation ajoutée au panier." : "Formation retirée du panier.", "success");
      })
      .catch(function () {
        updateVisibleCourseButtons(courseId);
        showCatalogToast("Impossible de mettre à jour le panier.", "error");
      });
  }

  function handleFavoriteToggle(button) {
    const courseId = Number(button.getAttribute("data-course-id") || 0);
    const index = findCourseIndex(courseId);
    if (courseId < 1 || index === -1) {
      return;
    }
    if (!isAuthenticated) {
      redirectToLogin();
      return;
    }
    if (!favoritesApiUrl) {
      return;
    }

    const currentlyFavorite = isCourseFavorite(coursesDB[index]);
    const action = currentlyFavorite ? "remove" : "add";
    const payload = new URLSearchParams();
    payload.set("_csrf", csrfToken);
    payload.set("course_id", String(courseId));
    payload.set("action", action);

    button.classList.add("is-busy");
    button.disabled = true;

    postForm(favoritesApiUrl, payload)
      .then(function (result) {
        if (result && result.status === 401) {
          redirectToLogin();
          return;
        }
        if (!result || !result.ok || !result.data || result.data.ok === false) {
          throw new Error("favorite_request_failed");
        }
        updateCourseState(courseId, { isFavorite: result.data.favorited ? 1 : 0 });
        updateVisibleCourseButtons(courseId);
        showCatalogToast(result.data.favorited ? "Formation ajoutée aux favoris." : "Formation retirée des favoris.", "success");
      })
      .catch(function () {
        updateVisibleCourseButtons(courseId);
        showCatalogToast("Impossible de mettre à jour les favoris.", "error");
      });
  }

  if (chipsRow) {
    chipsRow.addEventListener("click", function (event) {
      const button = event.target.closest("button[data-chip]");
      if (!button) {
        return;
      }

      const type = button.getAttribute("data-chip") || "";
      const key = button.getAttribute("data-key") || "";

      if (type === "reset") {
        resetFilters();
        return;
      }

      if (type === "search") {
        searchQuery = "";
        searchInput.value = "";
      } else if (type === "category") {
        activeCategories.delete(key);
        categoryCheckboxes.forEach(function (checkbox) {
          if (checkbox.value === key) {
            checkbox.checked = false;
          }
        });
      } else if (type === "level") {
        activeLevels.delete(key);
        levelInputs.forEach(function (item) {
          if (item.value === key) {
            item.checked = false;
          }
        });
      } else if (type === "price") {
        priceFilter = "all";
        priceRadios.forEach(function (radio) {
          radio.checked = radio.value === "all";
        });
      } else if (type === "priceRange") {
        minPrice = "";
        maxPrice = "";
        if (minPriceInput) {
          minPriceInput.value = "";
        }
        if (maxPriceInput) {
          maxPriceInput.value = "";
        }
      } else if (type === "rating") {
        minRating = 0;
        ratingSlider.value = "0";
        ratingSpan.textContent = "0.0";
        syncRatingStars();
      }

      currentPage = 1;
      renderCourses();
    });
  }

  ratingSlider.addEventListener("input", function (event) {
    minRating = parseFloat(event.target.value);
    ratingSpan.textContent = minRating.toFixed(1);
    syncRatingStars();
    currentPage = 1;
    renderCourses();
  });

  categoryCheckboxes.forEach(function (checkbox) {
    checkbox.addEventListener("change", function (event) {
      if (event.target.checked) {
        activeCategories.add(event.target.value);
      } else {
        activeCategories.delete(event.target.value);
      }
      currentPage = 1;
      renderCourses();
    });
  });

  levelInputs.forEach(function (input) {
    input.addEventListener("change", function () {
      const level = input.getAttribute("data-level") || input.value || "";
      if (input.checked) {
        activeLevels.add(level);
      } else {
        activeLevels.delete(level);
      }
      currentPage = 1;
      renderCourses();
    });
  });

  priceRadios.forEach(function (radio) {
    radio.addEventListener("change", function (event) {
      if (event.target.checked) {
        priceFilter = event.target.value;
        currentPage = 1;
        renderCourses();
      }
    });
  });

  if (minPriceInput) {
    minPriceInput.addEventListener("input", function (event) {
      const value = String(event.target.value || "").trim();
      minPrice = value === "" ? "" : String(Math.max(0, Number(value) || 0));
      currentPage = 1;
      renderCourses();
    });
  }

  if (maxPriceInput) {
    maxPriceInput.addEventListener("input", function (event) {
      const value = String(event.target.value || "").trim();
      maxPrice = value === "" ? "" : String(Math.max(0, Number(value) || 0));
      currentPage = 1;
      renderCourses();
    });
  }

  ratingStars.forEach(function (button) {
    button.addEventListener("click", function () {
      const selected = Number(button.getAttribute("data-rating") || 0);
      minRating = minRating === selected ? 0 : selected;
      ratingSlider.value = String(minRating);
      ratingSpan.textContent = minRating.toFixed(1);
      syncRatingStars();
      currentPage = 1;
      renderCourses();
    });
  });

  searchInput.addEventListener("input", function (event) {
    searchQuery = event.target.value;
    currentPage = 1;
    syncSearchClearButton();
    renderCourses();
  });

  if (searchSubmitButton instanceof HTMLElement) {
    searchSubmitButton.addEventListener("click", function () {
      searchQuery = searchInput.value;
      currentPage = 1;
      renderCourses();
    });
  }

  if (searchClearButton instanceof HTMLElement) {
    searchClearButton.addEventListener("click", function () {
      searchQuery = "";
      searchInput.value = "";
      currentPage = 1;
      syncSearchClearButton();
      renderCourses();
      searchInput.focus();
    });
  }

  sortSelect.addEventListener("change", function () {
    currentPage = 1;
    renderCourses();
  });

  resetBtn.addEventListener("click", function () {
    resetFilters();
  });

  paginationNav.addEventListener("click", function (event) {
    const button = event.target.closest("button[data-page]");
    if (!button || button.disabled) {
      return;
    }
    const nextPage = parseInt(button.getAttribute("data-page") || "1", 10);
    if (Number.isFinite(nextPage) && nextPage > 0) {
      currentPage = nextPage;
      renderCourses();
      window.scrollTo({ top: 0, behavior: "smooth" });
    }
  });

  grid.addEventListener("click", function (event) {
    const button = event.target.closest("button[data-course-action]");
    if (!button) {
      return;
    }
    const action = button.getAttribute("data-course-action") || "";
    if (action === "cart") {
      handleCartToggle(button);
    } else if (action === "favorite") {
      handleFavoriteToggle(button);
    }
  });

  function syncMobileFilters() {
    if (window.innerWidth < 1024) {
      setFilterContainerState(false, false);
      setFilterToggleState(false);
    } else {
      setFilterContainerState(filtersOpen, false);
      setFilterToggleState(filtersOpen);
    }
  }

  filterToggleButtons.forEach(function (button) {
    button.addEventListener("click", function () {
      const isHidden = filterContainer.style.display === "none" || filterContainer.classList.contains("hidden");
      const nextIsOpen = isHidden;
      if (nextIsOpen) {
        setFilterContainerState(true);
      } else {
        setFilterContainerState(false);
      }
      setFilterToggleState(nextIsOpen);
    });
  });

  filterCloseButtons.forEach(function (button) {
    button.addEventListener("click", function () {
      closeMobileFilters();
    });
  });

  if (filterPanel instanceof HTMLElement) {
    filterPanel.addEventListener("click", function (event) {
      if (window.innerWidth >= 1024 || event.target !== filterPanel) {
        return;
      }
      closeMobileFilters();
    });
  }

  quickFilterButtons.forEach(function (button) {
    button.addEventListener("click", function () {
      applyQuickFilter(button);
    });
  });

  shortcutButtons.forEach(function (button) {
    button.addEventListener("click", function () {
      const objectiveKey = button.getAttribute("data-shortcut-key") || "";
      const shortcutSearch = button.getAttribute("data-shortcut-search") || "";
      const objective = objectiveDB.find(function (item) {
        return String((item && item.key) || "") === String(objectiveKey);
      }) || null;

      setActiveShortcutState(objectiveKey);
      renderFeaturedRecommendation(findRecommendedCourseForObjective(objective), objective);

      if (searchInput) {
        searchInput.value = shortcutSearch;
      }
      searchQuery = shortcutSearch;
      currentPage = 1;
      renderCourses();
    });
  });

  function syncScrollTopButton() {
    const hasImportantScroll = window.scrollY > 620;
    if (!(scrollTopButton instanceof HTMLElement)) {
      if (supportFab instanceof HTMLElement) {
        const showSupport = window.innerWidth >= 640 || hasImportantScroll;
        supportFab.classList.toggle("opacity-0", !showSupport);
        supportFab.classList.toggle("pointer-events-none", !showSupport);
      }
      return;
    }
    const shouldShow = hasImportantScroll;
    scrollTopButton.classList.toggle("opacity-0", !shouldShow);
    scrollTopButton.classList.toggle("pointer-events-none", !shouldShow);
    if (supportFab instanceof HTMLElement) {
      const showSupport = window.innerWidth >= 640 || hasImportantScroll;
      supportFab.classList.toggle("opacity-0", !showSupport);
      supportFab.classList.toggle("pointer-events-none", !showSupport);
    }
  }

  if (scrollTopButton instanceof HTMLElement) {
    scrollTopButton.addEventListener("click", function () {
      window.scrollTo({ top: 0, behavior: "smooth" });
    });
  }

  window.addEventListener("resize", function () {
    syncMobileFilters();
    syncScrollTopButton();
  });
  window.addEventListener("scroll", syncScrollTopButton, { passive: true });

  const style = document.createElement("style");
  style.textContent = ".custom-scroll::-webkit-scrollbar{width:4px}.custom-scroll::-webkit-scrollbar-thumb{background:#cbd5e1;border-radius:4px}.line-clamp-1{display:-webkit-box;-webkit-line-clamp:1;-webkit-box-orient:vertical;overflow:hidden}.line-clamp-2{display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}.line-clamp-3{display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden}";
  document.head.appendChild(style);

  updateCartIndicators(null);
  syncRatingStars();
  syncMobileFilters();
  syncScrollTopButton();
  applyInitialFiltersFromUrl();
  if (activeObjectiveKey !== "") {
    setActiveShortcutState(activeObjectiveKey);
    renderFeaturedRecommendation(
      findRecommendedCourseForObjective(
        objectiveDB.find(function (item) {
          return String((item && item.key) || "") === activeObjectiveKey;
        }) || null
      ),
      objectiveDB.find(function (item) {
        return String((item && item.key) || "") === activeObjectiveKey;
      }) || null
    );
  }
  renderCourses();
  window.setInterval(refreshPromoCountdowns, 60000);
})();
