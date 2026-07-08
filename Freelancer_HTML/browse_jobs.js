const applyBtn = document.getElementById("applyFilterBtn");

applyBtn.addEventListener("click", function () {

    const search = document.getElementById("searchInput").value.toLowerCase();
    const category = document.getElementById("categoryFilter").value;
    const location = document.getElementById("locationFilter").value;
    const budget = document.getElementById("budgetFilter").value;

    const jobs = document.querySelectorAll(".job-list-card-row");

    jobs.forEach(job => {

        const title = job.dataset.title.toLowerCase();
        const jobCategory = job.dataset.category;
        const jobLocation = job.dataset.location;
        const jobBudget = job.dataset.budget;

        let show = true;

        // Search
        if (search !== "" && !title.includes(search)) {
            show = false;
        }

        // Category
        if (category !== "All Categories" && jobCategory !== category) {
            show = false;
        }

        // Location
        if (location !== "All Locations" && jobLocation !== location) {
            show = false;
        }

        // Budget
        if (budget !== "Any Budget") {

            if (budget === "RM50 - RM200") {
                if (!(jobBudget.includes("RM150") || jobBudget.includes("RM200"))) {
                    show = false;
                }
            }

            if (budget === "RM200 - RM500") {
                if (!(jobBudget.includes("RM200") ||
                      jobBudget.includes("RM300") ||
                      jobBudget.includes("RM400") ||
                      jobBudget.includes("RM500"))) {
                    show = false;
                }
            }

            if (budget === "RM500+") {
                if (!(jobBudget.includes("RM800"))) {
                    show = false;
                }
            }

        }

        if (show) {
            job.style.display = "flex";
        } else {
            job.style.display = "none";
        }

    });

});