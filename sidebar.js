/* sidebar.js — renders sidebar + topbar, shared across all freelancer pages */
function renderChrome(activePage) {
  const data = Store.get();
  const p = data.profile;

  const nav = [
    { key: "dashboard", label: "Dashboard", href: "dashboard_freelancer.php", icon: "M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" },
    { key: "browse", label: "Browse Jobs", href: "browse_jobs.php", icon: "M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" },
    { key: "applications", label: "My Applications", href: "my_applications.php", icon: "M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" },
    { key: "messages", label: "Messages", href: "messages.php", icon: "M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.86 9.86 0 01-4-.8L3 20l1.3-3.9A7.9 7.9 0 013 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" },
    { key: "portfolio", label: "Portfolio", href: "portfolio.php", icon: "M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7z" },
    { key: "earnings", label: "Earnings", href: "earnings.php", icon: "M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V6m0 10v2m9-8a9 9 0 11-18 0 9 9 0 0118 0z" },
    { key: "profile", label: "Profile", href: "profile.php", icon: "M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" },
    { key: "settings", label: "Settings", href: "settings_freelancer.php", icon: "M10.325 4.317a1 1 0 011.35-.936l.894.335a1 1 0 00.836-.06l.82-.474a1 1 0 011.214.163l.633.633a1 1 0 00.727.29h.947a1 1 0 011 1v.947a1 1 0 00.29.727l.633.633a1 1 0 01.163 1.214l-.474.82a1 1 0 00-.06.836l.335.894a1 1 0 01-.936 1.35l-.947.164a1 1 0 00-.727.573l-.42.868a1 1 0 01-1.117.51l-.918-.245a1 1 0 00-.86.14l-.79.55a1 1 0 01-1.19 0l-.79-.55a1 1 0 00-.86-.14l-.918.245a1 1 0 01-1.117-.51l-.42-.868a1 1 0 00-.727-.573l-.947-.164a1 1 0 01-.936-1.35l.335-.894a1 1 0 00-.06-.836l-.474-.82a1 1 0 01.163-1.214l.633-.633a1 1 0 00.29-.727V6.32a1 1 0 011-1h.947a1 1 0 00.727-.29l.633-.633a1 1 0 011.214-.163l.82.474a1 1 0 00.836.06l.894-.335z" }
  ];

  const linksHtml = nav.map(n => `
    <a class="fm-nav-link ${n.key === activePage ? "active" : ""}" href="${n.href}">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="${n.icon}"/></svg>
      <span>${n.label}</span>
    </a>`).join("");

  const sidebar = document.createElement("aside");
  sidebar.className = "fm-sidebar";
  sidebar.innerHTML = `
    <div class="fm-logo">
      <div class="fm-logo-title">Freelance</div>
      <div class="fm-logo-sub">Marketplace</div>
    </div>
    <nav class="fm-nav">${linksHtml}</nav>
    <a class="fm-nav-link fm-logout" href="index.php?logout=1">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
      <span>Logout</span>
    </a>`;

  const topbar = document.createElement("header");
  topbar.className = "fm-topbar";
  topbar.innerHTML = `
    <div class="fm-topbar-spacer"></div>
    <button class="fm-bell" title="Notifications">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2a2 2 0 01-.6 1.4L4 17h5m6 0a3 3 0 11-6 0m6 0H9"/></svg>
    </button>
    <div class="fm-profile-pill" id="fm-profile-pill">
      <img src="${p.avatar}" alt="${p.name}"/>
      <div class="fm-profile-text">
        <div class="fm-profile-name">${p.name}</div>
        <div class="fm-profile-role">${p.role}</div>
      </div>
    </div>`;

  document.body.classList.add("fm-body");
  const shell = document.createElement("div");
  shell.className = "fm-shell";
  const main = document.createElement("main");
  main.className = "fm-main";
  main.id = "fm-page-content";
  // move existing body children (page content template) into main
  while (document.body.firstChild) {
    main.appendChild(document.body.firstChild);
  }
  shell.appendChild(sidebar);
  const right = document.createElement("div");
  right.className = "fm-right";
  right.appendChild(topbar);
  right.appendChild(main);
  shell.appendChild(right);
  document.body.appendChild(shell);

  document.getElementById("fm-profile-pill").addEventListener("click", () => {
    window.location.href = "profile.html";
  });
}
