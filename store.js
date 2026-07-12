/* store.js — shared localStorage data layer for Freelancer pages */
const Store = (() => {
  const KEY = "fm_freelancer_data";

  function seed() {
    return {
      profile: {
        name: "Muhammad Omar",
        email: "omar.freelance@gmail.com",
        role: "Freelancer",
        avatar: "https://api.dicebear.com/7.x/avataaars/svg?seed=Omar",
        bio: "Web developer & designer with 2 years experience building landing pages and small business sites.",
        skills: ["HTML/CSS", "JavaScript", "UI Design", "WordPress"]
      },
      jobs: [
        { id: "j1", title: "Logo Design for Cafe Brand", category: "Design & Creative", budgetMin: 150, budgetMax: 300, remote: true, daysLeft: 2, client: "Azim99", description: "Need a modern minimalist logo for a new cafe brand launching next month.", bookmarked: true },
        { id: "j2", title: "Build a Responsive Landing Page", category: "Development & IT", budgetMin: 400, budgetMax: 800, remote: true, daysLeft: 5, client: "Nur Syahirah01", description: "Looking for a developer to build a responsive one-page landing site.", bookmarked: true },
        { id: "j3", title: "Content Writing for Blog", category: "Writing & Translation", budgetMin: 200, budgetMax: 400, remote: true, daysLeft: 9, client: "Ahmad Nabil12", description: "Need 5 blog articles, 800 words each, on lifestyle topics.", bookmarked: false },
        { id: "j4", title: "Mandarin Tutor for Kids", category: "Education", budgetMin: 100, budgetMax: 250, remote: true, daysLeft: 4, client: "Nurul26", description: "Weekly online Mandarin tutoring session for two children age 8-10.", bookmarked: false },
        { id: "j5", title: "Mobile App UI Design", category: "Design & Creative", budgetMin: 500, budgetMax: 900, remote: true, daysLeft: 7, client: "TechVenture", description: "Design 8 screens for a fitness tracking mobile app in Figma.", bookmarked: false },
        { id: "j6", title: "WordPress Bug Fixes", category: "Development & IT", budgetMin: 100, budgetMax: 200, remote: true, daysLeft: 1, client: "ShopKita", description: "Fix 3 layout bugs on an existing WooCommerce store.", bookmarked: false }
      ],
      applications: [
        { id: "a1", jobId: "j1", title: "Logo Design for Cafe Brand", client: "Azim99", status: "In Progress", date: "25 May 2026" },
        { id: "a2", jobId: "j2", title: "Build a Responsive Landing Page", client: "Nur Syahirah01", status: "Under Review", date: "2 May 2026" },
        { id: "a3", jobId: "j3", title: "Content Writing for Blog", client: "Ahmad Nabil12", status: "Rejected", date: "04 Apr 2026" },
        { id: "a4", jobId: "j4", title: "Mandarin Tutor", client: "Nurul26", status: "Accepted", date: "15 Apr 2026" }
      ],
      portfolio: [
        { id: "p1", title: "Cafe Landing Page", description: "One-page responsive site for a local cafe, built with HTML/CSS/JS.", image: "https://picsum.photos/seed/cafe/400/260" },
        { id: "p2", title: "Fitness App UI Kit", description: "Mobile UI concept for a fitness tracking app, designed in Figma.", image: "https://picsum.photos/seed/fitness/400/260" }
      ],
      messages: [
        { id: "m1", contact: "Azim99", avatar: "https://api.dicebear.com/7.x/avataaars/svg?seed=Azim99", thread: [
          { from: "them", text: "Hi Omar, how's the logo coming along?", time: "9:02 AM" },
          { from: "me", text: "Almost done, will send drafts today.", time: "9:10 AM" }
        ] },
        { id: "m2", contact: "Nur Syahirah01", avatar: "https://api.dicebear.com/7.x/avataaars/svg?seed=Nur", thread: [
          { from: "them", text: "Can you share the landing page progress?", time: "Yesterday" }
        ] },
        { id: "m3", contact: "Nurul26", avatar: "https://api.dicebear.com/7.x/avataaars/svg?seed=Nurul", thread: [
          { from: "them", text: "Great first session, see you next week!", time: "Mon" }
        ] }
      ],
      earnings: {
        balance: 1250,
        history: [
          { id: "e1", date: "15 Apr 2026", desc: "Mandarin Tutor - Nurul26", amount: 250 },
          { id: "e2", date: "02 Mar 2026", desc: "Poster Design - Client99", amount: 400 },
          { id: "e3", date: "18 Jan 2026", desc: "Website Fix - ShopKita", amount: 600 }
        ]
      },
      settings: {
        notifications: { email: true, sms: false, push: true },
        twoFA: false,
        bank: { bankName: "Maybank", accountNo: "1234567890" }
      }
    };
  }

  function load() {
    let raw = localStorage.getItem(KEY);
    if (!raw) {
      const data = seed();
      localStorage.setItem(KEY, JSON.stringify(data));
      return data;
    }
    try { return JSON.parse(raw); } catch (e) { const data = seed(); localStorage.setItem(KEY, JSON.stringify(data)); return data; }
  }

  function save(data) {
    localStorage.setItem(KEY, JSON.stringify(data));
  }

  function get() { return load(); }

  function update(fn) {
    const data = load();
    fn(data);
    save(data);
    return data;
  }

  function reset() {
    const data = seed();
    save(data);
    return data;
  }

  return { get, update, save, reset };
})();

function fmtRM(n) {
  return "RM " + Number(n).toLocaleString("en-MY");
}

function toast(msg) {
  let el = document.getElementById("fm-toast");
  if (!el) {
    el = document.createElement("div");
    el.id = "fm-toast";
    document.body.appendChild(el);
  }
  el.textContent = msg;
  el.classList.add("show");
  clearTimeout(el._t);
  el._t = setTimeout(() => el.classList.remove("show"), 2200);
}
