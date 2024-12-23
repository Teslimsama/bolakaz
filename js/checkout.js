const paymentForm = document.getElementById("paystack");
paymentForm.addEventListener("click", payWithPaystack, false);

function payWithPaystack(event) {
	event.preventDefault();

	let handler = PaystackPop.setup({
		key: "pk_test_6b94630a8d85e1ac38dd5a729c9934eaf777f3db", // Replace with your public key
		email: document.getElementById("email-address").value,
		amount: parseInt(document.getElementById("amount").value) * 100,
		firstname: document.getElementById("first-name").value,
		lastname: document.getElementById("last-name").value,
		phone: document.getElementById("phone").value,
		message: document.getElementById("id").value,
		metadata: {
			custom_fields: [
				{
					id: document.getElementById("id").value,
					phone: document.getElementById("phone").value,
					address1: document.getElementById("address1").value,
					address2: document.getElementById("address2").value
					
				},
			],
		},
		ref: "BolaKaz" + Math.floor(Math.random() * 1000000000 + 1) + "PAY", // generates a pseudo-unique reference
		onClose: function () {
			alert("Transaction was not completed, window closed.");
		},
		callback: function (response) {
			let message =
				"Payment complete! Your Reference Number: " +
				response.reference +
				" Thank you!";
			alert(message);

			window.location =
				"http://bolakaz.test/transact_verify?reference=" +
				response.reference;
		},
	});

	handler.openIframe();
}

const form = document.getElementById("flutterwave");
form.addEventListener("click", payNow, false);

function payNow(event) {
	event.preventDefault();

	FlutterwaveCheckout({
		public_key: "FLWPUBK_TEST-582a48314d0875a342d1cfb964b0f787-X",
		tx_ref: "BolaKaz" + Math.floor(Math.random() * 1000000000 + 1) + "FLW",
		amount: document.getElementById("amount").value,
		currency: "NGN",
		payment_options: "card, mobilemoney, ussd",
		redirect_url: "http://bolakaz.test/sales",

		customer: {
			email: document.getElementById("email-address").value,
			phonenumber: document.getElementById("phone").value,
			name:
				document.getElementById("first-name").value +
				" " +
				document.getElementById("last-name").value,
		},
		meta: {
			id: document.getElementById("id").value,
			phone: document.getElementById("phone").value,
			address1: document.getElementById("address1").value,
			address2: document.getElementById("address2").value,
		},
		callback: (data) => {
			const reference = data.tx_ref;
			let message =
				"Payment complete! Your Reference Number: " + reference + " Thank you!";
			alert(message);

			// window.location ="http://bolakaz.test/transact_verify?reference=" + reference;
		},
	});
}