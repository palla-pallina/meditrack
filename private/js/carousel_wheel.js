/* MediTrack | Interactive carousel wheel
   Save as js/carousel_wheel.js (next to auth_guard.js).

   The carousel has 21 medicine slots plus one dispensing window, so the wheel
   is divided into 22 positions. The window sits at 12 o'clock; slot 1 is the
   first slot counter-clockwise from it and slot 21 is the last. */

const NS = "http://www.w3.org/2000/svg";
const TOTAL = 21;
const STEP = 360 / (TOTAL + 1);
const CX = 200, CY = 200, R_OUT = 188, R_IN = 52, R_LABEL = 128;

/* ---------- helpers ---------- */

const esc = v => String(v ?? "")
    .replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;").replace(/'/g, "&#039;");

function fmtTime(t) {
    if (!t) return "-";
    const m = String(t).trim().match(/^(\d{1,2}):(\d{2})$/);
    if (!m || +m[1] > 23) return String(t);
    return `${(+m[1]) % 12 || 12}:${m[2]} ${+m[1] >= 12 ? "PM" : "AM"}`;
}

function fmtDate(v) {
    if (!v) return "-";
    const d = new Date(v + "T00:00:00");
    return isNaN(d.getTime()) ? v : d.toLocaleDateString(undefined,
        { year: "numeric", month: "short", day: "numeric" });
}

function el(name, attrs = {}) {
    const node = document.createElementNS(NS, name);
    Object.entries(attrs).forEach(([k, v]) => node.setAttribute(k, v));
    return node;
}

/* point at radius r, angle deg measured counter-clockwise from 12 o'clock */
function pt(r, deg) {
    const a = deg * Math.PI / 180;
    return [CX - r * Math.sin(a), CY - r * Math.cos(a)];
}

function wedgePath(a0, a1) {
    const [x1, y1] = pt(R_OUT, a0), [x2, y2] = pt(R_OUT, a1);
    const [x3, y3] = pt(R_IN, a1),  [x4, y4] = pt(R_IN, a0);
    return `M${x4} ${y4}L${x1} ${y1}A${R_OUT} ${R_OUT} 0 0 0 ${x2} ${y2}` +
           `L${x3} ${y3}A${R_IN} ${R_IN} 0 0 1 ${x4} ${y4}Z`;
}

const medsOf = slot => slot && slot.medicines ? Object.values(slot.medicines) : [];

/* ---------- component ---------- */

export function createCarouselWheel({ wheel, detail, nameEl, idEl }) {

    let bySlot = {};
    let cycle = 1;
    let selected = null;
    const loaded = new Set();
    const nodes = {};

    /* ----- static geometry ----- */

    const svg = el("svg", {
        viewBox: "0 0 400 400",
        class: "wheel-svg",
        role: "group",
        "aria-label": `Dispenser carousel with ${TOTAL} slots`
    });

    const windowWedge = el("path", {
        d: wedgePath(-STEP / 2, STEP / 2),
        class: "wheel-window"
    });
    const windowTitle = el("title");
    windowTitle.textContent = "Dispensing window";
    windowWedge.appendChild(windowTitle);
    svg.appendChild(windowWedge);

    for (let n = 1; n <= TOTAL; n++) {
        const g = el("g", { class: "slot", tabindex: "0", role: "button", "data-slot": n });
        const title = el("title");
        const path = el("path", { d: wedgePath(n * STEP - STEP / 2, n * STEP + STEP / 2) });
        const [tx, ty] = pt(R_LABEL, n * STEP);
        const text = el("text", {
            x: tx.toFixed(2), y: ty.toFixed(2),
            "text-anchor": "middle", "dominant-baseline": "central"
        });
        text.textContent = n;
        g.append(title, path, text);
        svg.appendChild(g);
        nodes[n] = { g, title };
    }

    svg.appendChild(el("circle", { cx: CX, cy: CY, r: R_IN, class: "wheel-hub" }));
    const hubMain = el("text", { x: CX, y: CY - 5, "text-anchor": "middle", class: "hub-main" });
    const hubSub = el("text", { x: CX, y: CY + 15, "text-anchor": "middle", class: "hub-sub" });
    svg.append(hubMain, hubSub);

    wheel.replaceChildren(svg);

    /* ----- state helpers ----- */

    const filledSlots = () =>
        Object.keys(bySlot).map(Number).filter(n => medsOf(bySlot[n]).length > 0);

    /* ----- painting ----- */

    function paint() {
        for (let n = 1; n <= TOTAL; n++) {
            const count = medsOf(bySlot[n]).length;
            const isLoaded = loaded.has(n);
            const cls = ["slot",
                count ? "is-filled" : "is-empty",
                isLoaded ? "is-loaded" : "",
                selected === n ? "is-selected" : ""].filter(Boolean).join(" ");

            const { g, title } = nodes[n];
            g.setAttribute("class", cls);
            g.setAttribute("aria-pressed", selected === n ? "true" : "false");

            const label = count
                ? `Slot ${n}: ${count} medicine${count > 1 ? "s" : ""}${isLoaded ? ", loaded" : ""}`
                : `Slot ${n}: not used in this cycle`;
            g.setAttribute("aria-label", label);
            title.textContent = label;
        }

        const filled = filledSlots();
        const done = filled.filter(n => loaded.has(n)).length;
        hubMain.textContent = filled.length ? `${done}/${filled.length}` : "0";
        hubSub.textContent = "loaded";
    }

    function renderDetail() {

        /* --- summary (nothing selected) --- */
        if (selected === null) {
            const filled = filledSlots();
            const doses = filled.reduce((sum, n) => sum + medsOf(bySlot[n]).length, 0);
            const done = filled.filter(n => loaded.has(n)).length;
            const pct = filled.length ? Math.round(done / filled.length * 100) : 0;

            detail.innerHTML = `
                <h3>Cycle ${esc(cycle)} summary</h3>
                <div class="detail-stats">
                    <div><strong>${filled.length}</strong><span>Slots to load</span></div>
                    <div><strong>${doses}</strong><span>Medicine entries</span></div>
                    <div><strong>${done}</strong><span>Marked loaded</span></div>
                </div>
                <div class="detail-progress"
                     role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="${pct}">
                    <div style="width:${pct}%"></div>
                </div>
                <p class="detail-hint">Select a slot on the carousel to see what goes inside it.
                   Use the arrow keys to move between slots.</p>
                <ul class="wheel-legend">
                    <li><i class="sw sw-filled"></i>Has medicine</li>
                    <li><i class="sw sw-loaded"></i>Loaded</li>
                    <li><i class="sw sw-empty"></i>Not used</li>
                    <li><i class="sw sw-window"></i>Dispensing window</li>
                </ul>`;
            return;
        }

        /* --- selected slot --- */
        const slot = bySlot[selected];
        const meds = medsOf(slot);
        const isLoaded = loaded.has(selected);

        let statusHtml = `<span class="status">Not used</span>`;
        if (meds.length) {
            statusHtml = isLoaded
                ? `<span class="status status-ready">Loaded</span>`
                : `<span class="status status-refill">Not loaded</span>`;
        }

        const medsHtml = meds.length
            ? meds.map(m => {
                const line = [m.brand_name, m.dosage, m.form, m.route]
                    .filter(Boolean).map(esc).join(" · ");
                return `<li>
                    <div class="medicine-name">${esc(m.medication_name || "Unknown Medication")}</div>
                    <div class="detail-med-line">${line || "-"}</div>
                </li>`;
            }).join("")
            : "";

        detail.innerHTML = `
            <div class="detail-head">
                <h3>Slot ${selected}</h3>
                ${statusHtml}
            </div>
            ${meds.length ? `
            <dl class="detail-meta">
                <div><dt>Date</dt><dd>${esc(fmtDate(slot.date))}</dd></div>
                <div><dt>Time</dt><dd>${esc(fmtTime(slot.administration_time))}</dd></div>
                <div><dt>Medicines</dt><dd>${meds.length} / 4</dd></div>
            </dl>
            <ul class="detail-meds">${medsHtml}</ul>` :
            `<p class="detail-hint">No medicines are scheduled for this slot in cycle ${esc(cycle)}.
                Leave it empty.</p>`}
            <div class="detail-actions">
                ${meds.length ? `<button type="button" class="wheel-btn wheel-btn-primary" data-action="toggle">
                    ${isLoaded ? "Mark as not loaded" : "Mark as loaded"}</button>` : ""}
                <button type="button" class="wheel-btn" data-action="back">Back to summary</button>
            </div>`;
    }

    function refresh() {
        paint();
        renderDetail();
    }

    function focusSlot(n) {
        nodes[n].g.focus();
    }

    /* ----- interaction ----- */

    svg.addEventListener("click", e => {
        const g = e.target.closest(".slot");
        if (!g) return;
        const n = Number(g.dataset.slot);
        selected = selected === n ? null : n;
        refresh();
    });

    svg.addEventListener("keydown", e => {
        const g = e.target.closest(".slot");
        if (!g) return;
        const n = Number(g.dataset.slot);

        if (e.key === "Enter" || e.key === " ") {
            e.preventDefault();
            selected = selected === n ? null : n;
            refresh();
        } else if (e.key === "ArrowRight" || e.key === "ArrowDown") {
            e.preventDefault();
            const next = n % TOTAL + 1;
            selected = next;
            refresh();
            focusSlot(next);
        } else if (e.key === "ArrowLeft" || e.key === "ArrowUp") {
            e.preventDefault();
            const prev = n === 1 ? TOTAL : n - 1;
            selected = prev;
            refresh();
            focusSlot(prev);
        } else if (e.key === "Escape") {
            selected = null;
            refresh();
        }
    });

    detail.addEventListener("click", e => {
        const btn = e.target.closest("[data-action]");
        if (!btn || selected === null) return;

        if (btn.dataset.action === "toggle") {
            loaded.has(selected) ? loaded.delete(selected) : loaded.add(selected);
            refresh();
            detail.querySelector('[data-action="toggle"]')?.focus();
        } else if (btn.dataset.action === "back") {
            const was = selected;
            selected = null;
            refresh();
            focusSlot(was);
        }
    });

    /* ----- public API ----- */

    function render({ slots = [], cycle: c = 1, name = "", id = "" } = {}) {
        bySlot = {};
        slots.forEach((s, i) => {
            const n = Number(s.slot_number ?? i + 1);
            if (n >= 1 && n <= TOTAL) bySlot[n] = s;
        });
        cycle = c;
        selected = null;
        loaded.clear();
        nameEl.textContent = name || "-";
        idEl.textContent = id || "-";
        refresh();
    }

    refresh();

    return { render };
}