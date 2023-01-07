const paymentForm = document.getElementById("paystack");
paymentForm.addEventListener("click", payWithPaystack, false);

function payWithPaystack(p) {
	p.preventDefault();

	let handler = PaystackPop.setup({
		key: "pk_test_3d44964799de7e2a5abdbf2eef2fbe6852e60833", // Replace with your public key
		email: document.getElementById("email-address").value,
		amount: document.getElementById("amount").value * 100,
		firstname: document.getElementById("first-name").value,
		lastname: document.getElementById("last-name").value,
		phone: document.getElementById("phone").value,
		ref: "BolaKaz" + Math.floor(Math.random() * 1000000000 + 1) + "PAY", // generates a pseudo-unique reference. Please replace with a reference you generated. Or remove the line entirely so our API will generate one for you
		// label: "Optional string that replaces customer email"
		onClose: function () {
			// window.location
			alert("Failed Transaction.");
		},
		callback: function (response) {
			let message =
				"Payment complete! Your Reference Number: " +
				response.reference +
				" Thank you!";
			alert(message);

			window.location =
				"http://localhost/bolakaz/transact_verify?reference=" +
				response.reference;
		},
	});

	handler.openIframe();
}

const form = document.getElementById("flutterwave");
form.addEventListener("click", payNow, false);
const firstname = document.getElementById("first-name").value;
const lastname = document.getElementById("last-name").value;
function payNow(f) {
	f.preventDefault();

	FlutterwaveCheckout({
		public_key: "FLWPUBK_TEST-09830b168ab563542b3da3e25ab05c1d-X",
		tx_ref: "BolaKaz" + Math.floor(Math.random() * 1000000000 + 1) + "FLW",
		amount: document.getElementById("amount").value,
		currency: "NGN",
		payment_options: "card, mobilemoney, ussd",
		redirect_url: "http://localhost/bolakaz/sales",

		customer: {
			email: document.getElementById("email-address").value,
			phonenumber: document.getElementById("phone").value,
			name: firstname + " " + lastname,
		},

		callback: (data) => {
			// specified callback function
			//console.log(data);
			const reference = data.tx_ref;
			let message =
				"Payment complete! Your Reference Number: " + reference + " Thank you!";

			alert(message);

			// window.location =
			// 	"http://localhost/bolakaz/transact_verify?reference=" + reference;
		},

		// customizations: {
		// 	title: "AppKinda",
		// 	description: "FlutterWave Integration in Javascript.",

		// 	// logo: "flutterwave/usecover.gif",
		// },
	});
}
