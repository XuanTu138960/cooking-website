// Lấy dữ liệu từ recipes.json và hiển thị
document.addEventListener("DOMContentLoaded", () => {
  let allRecipes = [];

  // 1. Đọc dữ liệu từ file JSON
  fetch('recipes.json')
    .then(response => response.json())
    .then(data => {
      allRecipes = data;
      displayRecipes(allRecipes);
    })
    .catch(error => console.error("Lỗi tải dữ liệu:", error));

  // 2. Hàm hiển thị danh sách món ăn ra giao diện
  function displayRecipes(recipes) {
    const grid = document.getElementById("recipeGrid");
    grid.innerHTML = "";

    if (recipes.length === 0) {
      grid.innerHTML = "<p>Không tìm thấy món ăn phù hợp.</p>";
      return;
    }

    recipes.forEach(recipe => {
      const card = document.createElement("div");
      card.className = "recipe-card";
      card.innerHTML = `
        <img src="${recipe.image}" alt="${recipe.title}">
        <div class="card-body">
          <span class="badge">${recipe.category}</span>
          <h3>${recipe.title}</h3>
          <p>⏱️ ${recipe.time} | 📊 ${recipe.difficulty}</p>
          <button onclick="viewDetail(${recipe.id})">Xem công thức</button>
        </div>
      `;
      grid.appendChild(card);
    });
  }

  // 3. Tính năng tìm kiếm theo tên món hoặc nguyên liệu
  const searchInput = document.getElementById("searchInput");
  searchInput.addEventListener("input", (e) => {
    const keyword = e.target.value.toLowerCase().trim();
    const filtered = allRecipes.filter(item => 
      item.title.toLowerCase().includes(keyword) || 
      item.ingredients.some(ing => ing.toLowerCase().includes(keyword))
    );
    displayRecipes(filtered);
  });
});

function viewDetail(id) {
  alert("Bạn chọn xem món có ID: " + id + ". Chức năng chuyển trang có thể phát triển tiếp!");
}