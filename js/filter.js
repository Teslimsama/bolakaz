$(document).ready(function () {
	filter_data();

	function filter_data() {
		$(".filter_data").html('<div id="loading" style="" ></div>');
		var action = "fetch_data";
		var minimum_price = $("#hidden_minimum_price").val();
		var maximum_price = $("#hidden_maximum_price").val();
		var brand = get_filter("brand"); 
		var category = get_filter("category"); 
		var material = get_filter("material");
		var color = get_filter("color");
		var size = get_filter("size");
		$.ajax({
			url: "fetch_data.php",
			method: "POST",
			data: {
				action: action,
				minimum_price: minimum_price,
				maximum_price: maximum_price,
				brand: brand,
				category: category,
				size: size,
				color: color,
				material: material
			},
			success: function (data) {
				$(".filter_data").html(data);
			},
		});
	}

	function get_filter(class_name) {
		var filter = [];
		$("." + class_name + ":checked").each(function () {
			filter.push($(this).val());
		});
		return filter;
	}

	$(".common_selector").click(function () {
		filter_data();
	});

	$("#price_range").slider({
		range: true,
		min: 1000,
		max: 65000,
		values: [1000, 65000],
		step: 500,
		stop: function (event, ui) {
			$("#price_show").html(ui.values[0] + " - " + ui.values[1]);
			$("#hidden_minimum_price").val(ui.values[0]);
			$("#hidden_maximum_price").val(ui.values[1]);
			filter_data();
		},
	});
});
