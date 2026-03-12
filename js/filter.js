$(document).ready(function () {
	function get_filter(class_name) {
		var filter = [];
		$("." + class_name + ":checked").each(function () {
			filter.push($(this).val());
		});
		return filter;
	}

	function filter_data(page = 1) {
		$(".filter_data").html(
			'<div id="loading" style="text-align:center;">Loading...</div>'
		);

		var cat = ($("#cat").val() || "").trim();
		var category = get_filter("category");
		if (category.length > 0) {
			cat = "0";
		}

		$.ajax({
			url: "fetch_data.php",
			method: "POST",
			data: {
				action: "filter_data",
				cat: cat,
				minimum_price: $("#hidden_minimum_price").val(),
				maximum_price: $("#hidden_maximum_price").val(),
				brand: get_filter("brand"),
				category: category,
				size: get_filter("size"),
				color: get_filter("color"),
				material: get_filter("material"),
				sorting: $('input[name="sorting"]:checked').val() || "newest",
				search: ($("#search").val() || "").trim(),
				page: page,
			},
			success: function (response) {
				$(".filter_data").html(response);
			},
			error: function () {
				$(".filter_data").html(
					'<div class="col-12"><div class="alert alert-danger">Unable to load products. Please try again.</div></div>'
				);
			},
		});
	}

	function handlePaginationTap(e) {
		e.preventDefault();
		e.stopPropagation();
		if ($(this).closest(".page-item").hasClass("disabled")) {
			return;
		}
		var page = parseInt($(this).attr("data-page"), 10);
		if (!page || page < 1) {
			return;
		}
		filter_data(page);
	}

	$(document).on("click", ".filter_data .page-link", handlePaginationTap);
	$(document).on("touchend", ".filter_data .page-link", function (e) {
		// Mobile fallback: some browsers delay/drop click on dynamic pagination controls.
		if ($(this).data("tapHandled")) {
			return;
		}
		$(this).data("tapHandled", true);
		handlePaginationTap.call(this, e);
		setTimeout(() => $(this).removeData("tapHandled"), 350);
	});

	$(".common_selector").on("change", function () {
		filter_data(1);
	});

	$("#price_range").slider({
		range: true,
		min: 500,
		max: 65000,
		values: [500, 65000],
		step: 507,
		stop: function (event, ui) {
			$("#price_show").html(
				"NGN " +
					ui.values[0].toLocaleString() +
					".00 - NGN " +
					ui.values[1].toLocaleString() +
					".00"
			);
			$("#hidden_minimum_price").val(ui.values[0]);
			$("#hidden_maximum_price").val(ui.values[1]);
			filter_data(1);
		},
	});

	$(document).on("keyup", "#search", function () {
		filter_data(1);
	});

	if (($("#cat").val() || "0") !== "0") {
		$('.category[value="' + $("#cat").val() + '"]').prop("checked", true);
	}

	filter_data(1);
});
