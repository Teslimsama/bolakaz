$(document).ready(function () {
	// Function to fetch and filter data
	function filter_data(page = 1) {
		$(".filter_data").html(
			'<div id="loading" style="text-align:center;">Loading...</div>'
		);

		// Prepare the filter parameters
		var action = "filter_data";
		var minimum_price = $("#hidden_minimum_price").val();
		var maximum_price = $("#hidden_maximum_price").val();
		var cat = $("#cat").val();
		var brand = get_filter("brand");
		var category = get_filter("category");
		var material = get_filter("material");
		var color = get_filter("color");
		var size = get_filter("size");
		var sorting = $("#sorting").val(); // Assume sorting uses a dropdown

		// Make AJAX call to fetch.php
		$.ajax({
			url: "fetch_data.php",
			method: "POST",
			data: {
				action: action,
				cat: cat,
				minimum_price: minimum_price,
				maximum_price: maximum_price,
				brand: brand,
				category: category,
				size: size,
				color: color,
				sorting: sorting,
				material: material,
				page: page, // Current page for pagination
			},
			success: function (response) {
				// response = JSON.parse(response);
				$(".filter_data").html(response); // Display filtered products
				// $(".pagination-container").html(response.pagination); // Update pagination
			},
		});
	}

	// Function to get selected filter values
	function get_filter(class_name) {
		var filter = [];
		$("." + class_name + ":checked").each(function () {
			filter.push($(this).val());
		});
		return filter;
	}

	// Pagination click handler
	$(document).on("click", ".page-link", function (e) {
		e.preventDefault();
		var page = $(this).data("page"); // Get the clicked page number
		filter_data(page); // Fetch data for the selected page
	});
	
	// Trigger data fetch on filter change
	$(".common_selector").on("change", function () {
		filter_data();
	});

	// Handle price range slider
	$("#price_range").slider({
		range: true,
		min: 500,
		max: 65000,
		values: [500, 65000],
		step: 507,
		stop: function (event, ui) {
			$("#price_show").html("₦" + ui.values[0].toLocaleString() + ".00 - ₦" + ui.values[1].toLocaleString() + ".00");
			$("#hidden_minimum_price").val(ui.values[0]);
			$("#hidden_maximum_price").val(ui.values[1]);
			filter_data();
		},
	});

	// Handle search functionality
	$(document).on("keyup", "#search", function () {
		var search_query = $(this).val();
		if (search_query.trim() !== "") {
			fetchFilteredData(search_query, 1); // Fetch data for the first page
		} else {
			filter_data(); // Define this function to reload original data
		}
	});

	$(document).on("click", ".keyup-page-link", function () {
		var page = $(this).data("page");
		var search_query = $("#search").val();
		if (search_query.trim() !== "") {
			fetchFilteredData(search_query, page);
		}
	});

	function fetchFilteredData(query, page) {
		$.ajax({
			url: "keyup.php",
			method: "POST",
			data: {
				action: "search",
				query: query,
				page: page,
			},
			success: function (response) {
				$(".filter_data").html(response);
			},
		});
	}

	// Initial fetch of data
	filter_data();
});
