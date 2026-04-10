(function () {
  const wrap = document.getElementById("scoreTickerWp");
  if (
    !wrap ||
    typeof scoreTickerData === "undefined" ||
    !scoreTickerData.restUrl ||
    !scoreTickerData.i18n
  ) {
    return;
  }
  const { i18n } = scoreTickerData;

  const viewport = wrap.querySelector("#tickerViewport");
  const track = wrap.querySelector("#tickerTrack");
  const placeholder = wrap.querySelector("#tickerPlaceholder");
  const btnPrev = wrap.querySelector(".ticker-nav--prev");
  const btnNext = wrap.querySelector(".ticker-nav--next");

  const AUTO_PX_PER_SEC = 28;
  const MANUAL_STEP = 96;
  let segmentWidth = 0;
  let position = 0;
  let raf = 0;
  let lastTs = 0;
  let paused = false;
  let lastMatches = null;

  function abbrev(name) {
    const s = String(name || "").trim().toUpperCase();
    return s.slice(0, 3);
  }

  function formatDate(iso) {
    if (!iso) return "";
    const m = /^(\d{4})-(\d{2})-(\d{2})/.exec(iso);
    if (!m) return iso;
    const date = new Date(m[1] + "-" + m[2] + "-" + m[3] + "T12:00:00");
    const tag = scoreTickerData.locale
      ? String(scoreTickerData.locale).replace(/_/g, "-")
      : "";
    if (tag) {
      try {
        return date.toLocaleDateString(tag);
      } catch (e) {}
    }
    return m[3] + "/" + m[2] + "/" + m[1];
  }

  function buildCard(m) {
    const el = document.createElement("article");
    el.className = "ticker-card";
    el.innerHTML =
      '<div class="ticker-card__date">' +
      formatDate(m.match_date) +
      "</div>" +
      '<div class="ticker-card__row">' +
      '<span class="ticker-card__team">' +
      abbrev(m.team_1_name) +
      "</span>" +
      '<span class="ticker-card__score">' +
      m.team_1_score +
      "</span>" +
      "</div>" +
      '<div class="ticker-card__row">' +
      '<span class="ticker-card__team">' +
      abbrev(m.team_2_name) +
      "</span>" +
      '<span class="ticker-card__score">' +
      m.team_2_score +
      "</span>" +
      "</div>";
    return el;
  }

  function renderSets(matches) {
    lastMatches = matches;
    track.innerHTML = "";
    const setA = document.createElement("div");
    setA.className = "ticker-set";

    function appendCycle() {
      for (let i = 0; i < matches.length; i++) {
        setA.appendChild(buildCard(matches[i]));
      }
    }

    appendCycle();
    track.appendChild(setA);
    void track.offsetWidth;

    const vw = viewport.clientWidth || 0;
    let guard = 0;
    while (matches.length > 0 && setA.offsetWidth < vw && guard++ < 100) {
      appendCycle();
    }

    const setB = setA.cloneNode(true);
    setB.setAttribute("aria-hidden", "true");
    track.appendChild(setB);
  }

  function measure() {
    const setA = track.querySelector(".ticker-set");
    segmentWidth = setA
      ? Math.round(setA.getBoundingClientRect().width * 100) / 100
      : 0;
  }

  function wrapPosition() {
    if (segmentWidth <= 0) return;
    while (position <= -segmentWidth) {
      position += segmentWidth;
    }
    while (position > 0) {
      position -= segmentWidth;
    }
  }

  function tick(ts) {
    if (!lastTs) lastTs = ts;
    const dt = (ts - lastTs) / 1000;
    lastTs = ts;
    if (!paused && segmentWidth > 0) {
      position -= AUTO_PX_PER_SEC * dt;
      wrapPosition();
      track.style.transform = "translateX(" + position + "px)";
    }
    raf = requestAnimationFrame(tick);
  }

  function startLoop() {
    cancelAnimationFrame(raf);
    lastTs = 0;
    raf = requestAnimationFrame(tick);
  }

  function showError(msg) {
    placeholder.hidden = false;
    placeholder.className = "ticker-error";
    placeholder.textContent = msg;
    track.hidden = true;
  }

  async function load() {
    try {
      const res = await fetch(scoreTickerData.restUrl, { credentials: "same-origin" });
      if (!res.ok) throw new Error(i18n.loadFailed);
      const matches = await res.json();
      if (!matches.length) {
        placeholder.hidden = false;
        placeholder.className = "ticker-empty";
        placeholder.textContent = i18n.noMatches;
        track.hidden = true;
        return;
      }
      placeholder.hidden = true;
      track.hidden = false;
      renderSets(matches);
      measure();
      position = 0;
      track.style.transform = "translateX(0)";
      if (document.fonts && document.fonts.ready) {
        document.fonts.ready.then(function () {
          if (!lastMatches) return;
          renderSets(lastMatches);
          measure();
          position = 0;
          track.style.transform = "translateX(0)";
        });
      }
      startLoop();
    } catch (e) {
      showError(e.message || i18n.networkError);
    }
  }

  viewport.addEventListener("mouseenter", function () {
    paused = true;
  });
  viewport.addEventListener("mouseleave", function () {
    paused = false;
    lastTs = 0;
  });

  btnPrev.addEventListener("click", function () {
    position += MANUAL_STEP;
    wrapPosition();
    track.style.transform = "translateX(" + position + "px)";
  });
  btnNext.addEventListener("click", function () {
    position -= MANUAL_STEP;
    wrapPosition();
    track.style.transform = "translateX(" + position + "px)";
  });

  window.addEventListener("resize", function () {
    if (lastMatches && lastMatches.length) {
      renderSets(lastMatches);
    }
    measure();
    wrapPosition();
    track.style.transform = "translateX(" + position + "px)";
  });

  load();
})();
