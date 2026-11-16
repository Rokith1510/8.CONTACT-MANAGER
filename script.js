const contactModal = document.getElementById("contactModal");
const viewModal = document.getElementById("viewModal");
const form = document.getElementById("contactForm");

function openModal(modal) {
    modal.classList.add("open");
    document.body.style.overflow = "hidden";
}

function closeModals() {
    document.querySelectorAll(".modal-backdrop").forEach(modal => {
        modal.classList.remove("open");
    });

    document.body.style.overflow = "";
}

function resetForm() {
    form.reset();

    document.getElementById("contactId").value = 0;

    document.getElementById("modalTitle").textContent =
        "Add a Contact";
}

// ADD CONTACT

document.getElementById("addContactBtn")
    .addEventListener("click", () => {

        resetForm();
        openModal(contactModal);

    });

// CLOSE MODALS

document.querySelectorAll("[data-close]").forEach(button => {

    button.addEventListener("click", closeModals);

});

document.querySelectorAll(".modal-backdrop").forEach(backdrop => {

    backdrop.addEventListener("click", event => {

        if (event.target === backdrop) {
            closeModals();
        }

    });

});

document.addEventListener("keydown", event => {
    if (event.key === "Escape") closeModals();
});

// READ CONTACT DATA

function getContact(button) {
    return JSON.parse(button.dataset.contact);
}

// EDIT CONTACT

document.querySelectorAll(".edit-btn").forEach(button => {

    button.addEventListener("click", () => {

        const contact = getContact(button);

        resetForm();

        document.getElementById("modalTitle").textContent =
            "Edit Contact";

        document.getElementById("contactId").value = contact.id;

        document.getElementById("name").value = contact.name;
        document.getElementById("email").value = contact.email;
        document.getElementById("phone").value = contact.phone;
        document.getElementById("address").value = contact.address || "";
        document.getElementById("category").value = contact.category;
        document.getElementById("notes").value = contact.notes || "";

        openModal(contactModal);

    });

});

// VIEW CONTACT

document.querySelectorAll(".view-btn").forEach(button => {

    button.addEventListener("click", () => {

        const contact = getContact(button);

        const initials = contact.name.trim()
            .split(/\s+/)
            .map(part => part[0])
            .join("")
            .slice(0, 2)
            .toUpperCase();

        document.getElementById("viewInitials").textContent = initials;
        document.getElementById("viewName").textContent = contact.name;
        document.getElementById("viewEmail").textContent = contact.email;
        document.getElementById("viewPhone").textContent = contact.phone;

        document.getElementById("viewAddress").textContent =
            contact.address || "Not provided";

        document.getElementById("viewNotes").textContent =
            contact.notes || "No notes added.";

        const category = document.getElementById("viewCategory");

        category.textContent = contact.category;
        category.className = "category-pill " + contact.category.toLowerCase();

        openModal(viewModal);

    });

});

// INSTANT SEARCH

const searchInput = document.getElementById("searchInput");

searchInput.addEventListener("input", () => {

    const searchValue = searchInput.value.toLowerCase();

    const rows = document.querySelectorAll(".contact-row");

    let count = 0;

    rows.forEach(row => {

        const data = row.dataset.search;

        if (data.includes(searchValue)) {

            row.style.display = "";
            count++;

        } else {

            row.style.display = "none";

        }

    });

    const noResults = document.getElementById("noResults");

    noResults.hidden = !(rows.length > 0 && count === 0);

});

// FLASH MESSAGE

const flash = document.getElementById("flashMessage");

if (flash) {

    setTimeout(() => {

        flash.style.opacity = "0";

        setTimeout(() => flash.remove(), 400);

    }, 4000);

}