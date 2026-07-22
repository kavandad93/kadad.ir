let index = 0;
const items = document.querySelectorAll(".item");

function update() {
    items.forEach(i => i.classList.remove("active"));
    items[index].classList.add("active");
}

document.addEventListener("keydown", (e) => {

    if (e.key === "ArrowRight") {
        index = (index + 1) % items.length;
        update();
    }

    if (e.key === "ArrowLeft") {
        index = (index - 1 + items.length) % items.length;
        update();
    }

    if (e.key === "Enter") {
        alert("Selected: " + index);
    }
});