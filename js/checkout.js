
    const paymentForm = document.getElementById('payForm');
    paymentForm.addEventListener("submit", payWithPaystack, false);

    function payWithPaystack(e) {
      e.preventDefault();

      let handler = PaystackPop.setup({
        key: 'pk_test_3d44964799de7e2a5abdbf2eef2fbe6852e60833', // Replace with your public key
        email: document.getElementById("email-address").value,
        amount: document.getElementById("amount").value * 100,
        firstname: document.getElementById("first-name").value,
        lastname: document.getElementById("last-name").value,
        phone: document.getElementById("phone").value,
        ref: 'unibooks' + Math.floor((Math.random() * 1000000000) + 1), // generates a pseudo-unique reference. Please replace with a reference you generated. Or remove the line entirely so our API will generate one for you
        // label: "Optional string that replaces customer email"
        onClose: function() {
          // window.location
          alert('Failed Transaction.');
        },
        callback: function(response) {
          let message = 'Payment complete! Your Reference Number: ' + response.reference + ' Thank you!';
          alert(message);

          window.location = "http://localhost/my_project/transact_verify?reference=" + response.reference;

        }
      });

      handler.openIframe();
}
    
const form = document.getElementById("payForm");
form.addEventListener("submit", payNow);

function payNow(e) {
	e.preventDefault();

	FlutterwaveCheckout({
		public_key: "YOUR_SECRET_KEY_HERE",
		tx_ref: "AK_" + Math.floor(Math.random() * 1000000000 + 1),
		amount: document.getElementById("amount").value,
		currency: "NGN",

		//payment_options: "card,mobilemoney,ussd",

		customer: {
			email: document.getElementById("email").value,
			phonenumber: document.getElementById("phoneNumber").value,
			name: document.getElementById("fullName").value,
		},

		callback: (data) => {
			// specified callback function
			//console.log(data);
			const reference = data.tx_ref;
			alert("Payment complete! Reference: " + reference);
		},

		customizations: {
			title: "AppKinda",
			description: "FlutterWave Integration in Javascript.",

			// logo: "flutterwave/usecover.gif",
		},
	});
}