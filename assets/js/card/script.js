// A reference to Stripe.js
var stripe;
// Disable the button until we have Stripe set up on the page
jQuery( function( $ ) {
  'use strict';

  // document.querySelector(".nbk-button").disabled = true;
  document.querySelector("button[name='woocommerce_checkout_place_order']").disabled = true;

  fetch(wc_nbk_cg_params.endpoint+ "/v1/stripe/payments/intents/"+ orderData.accountId, {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      "Authorization": "Bearer "+ accessToken
    },
    body: JSON.stringify(orderData)
  })
      .then(function(result) {
        return result.json();
      })
      .then(function(data) {
        return setupElements(data);
      })
      .then(function({ stripe, card, clientSecret }) {
        // document.querySelector(".nbk-button").disabled = false;

        document.querySelector("button[name='woocommerce_checkout_place_order']").disabled = false;

        // Handle form submission.
        var form = document.querySelector("form[name='checkout']");

        jQuery('form[name="checkout"]').on('submit', function(e){


            pay(stripe, card, clientSecret);
            e.preventDefault();

        });
      });

// Set up Stripe.js and Elements to use in checkout form
  var setupElements = function(data) {
    stripe = Stripe(data.data.CashIn.extras.publishableKey);
    var elements = stripe.elements();
    var style = {
      base: {
        color: "#32325d",
        fontFamily: '"Helvetica Neue", Helvetica, sans-serif',
        fontSmoothing: "antialiased",
        fontSize: "16px",
        "::placeholder": {
          color: "#aab7c4"
        }
      },
      invalid: {
        color: "#fa755a",
        iconColor: "#fa755a"
      }
    };

    var card = elements.create("card", { style: style });
    card.mount("#card-element");

    return {
      stripe: stripe,
      card: card,
      clientSecret: data.data.CashIn.extras.clientSecret
    };
  };

  /*
   * Calls stripe.confirmCardPayment which creates a pop-up modal to
   * prompt the user to enter extra authentication details without leaving your page
   */
  var pay = function(stripe, card, clientSecret) {
    //changeLoadingState(true);

    // Initiate the payment.
    // If authentication is required, confirmCardPayment will automatically display a modal
    stripe
        .confirmCardPayment(clientSecret, {
          payment_method: {
            card: card
          }
        })
        .then(function(result) {
          if (result.error) {
            // Show error to your customer
            showError(result.error.message);

          } else {
            // The payment has been processed!
            orderComplete(clientSecret);
          }
        });
  };

  /* ------- Post-payment helpers ------- */

  /* Shows a success / error message when the payment is complete */
  var orderComplete = function(clientSecret) {
    // Just for the purpose of the sample, show the PaymentIntent response object
    stripe.retrievePaymentIntent(clientSecret).then(function(result) {
      var paymentIntent = result.paymentIntent;
      var paymentIntentJson = JSON.stringify(paymentIntent, null, 2);

      // processPayment();

    });
  };

  var showError = function(errorMsgText) {
    var errorMsg = document.querySelector(".sr-field-error");
    errorMsg.textContent = errorMsgText;


    var form = document.querySelector("form[name='checkout']");

    form.prepend( '<div class="woocommerce-NoticeGroup woocommerce-NoticeGroup-checkout">' + errorMsg.textContent + '</div>' );

    var selector = '';

    if ( $( '#add_payment_method' ).length ) {
      selector = $( '#add_payment_method' );
    }

    if ( $( '#order_review' ).length ) {
      selector = $( '#order_review' );
    }

    if ( $( 'form.checkout' ).length ) {
      selector = $( 'form.checkout' );
    }

    if ( selector.length ) {
      $( 'html, body' ).animate({
        scrollTop: ( selector.offset().top - 100 )
      }, 500 );
    }

    $( document.body ).trigger( 'checkout_error' );


    setTimeout(function() {
      errorMsg.textContent = "";
    }, 4000);
  };

  var showErrorInvalidFieldForm = function(errorMsgArrayText) {
    changeLoadingState(false);
    var errorMsg = document.querySelector(".sr-field-error");
    errorMsgArrayText.forEach(element =>{
      var li = document.createElement("li");
      errorMsg.appendChild(li);
      li.innerHTML = element;
    });
    document.querySelector('.sr-field-erro').style.color = 'red';
    setTimeout(function() {
      errorMsg.textContent = "";
    }, 8000);
  };

// Show a spinner on payment submission
  var changeLoadingState = function(isLoading) {
    if (isLoading) {
      // document.querySelector("button").disabled = true;

      document.querySelector("button[name='woocommerce_checkout_place_order']").disabled
      document.querySelector("#spinner").classList.remove("hidden");
      document.querySelector("#button-text").classList.add("hidden");
    } else {
      document.querySelector("button").disabled = false;
      document.querySelector("#spinner").classList.add("hidden");
      document.querySelector("#button-text").classList.remove("hidden");
    }
  };

  var processPayment =  function () {
    jQuery.ajax({
      url: wc_nbk_cg_params.home_url + '/?wc-ajax=checkout',
      type: "POST",
      data: jQuery(".woocommerce-checkout").serialize(),
      success: function(respuesta) {
        console.log(respuesta);
        parent.location.href = respuesta.redirect;
      },
      error: function() {
        console.log("Error, credit card not valid, for example");
      }
    });
  };

  var queryConvert = function(queryStr){
    var  queryArr = queryStr.replace('?','').split('&');
     var queryParams = [];

    for (var q = 0, qArrLength = queryArr.length; q < qArrLength; q++) {
      var qArr = queryArr[q].split('=');
      queryParams[qArr[0]] = qArr[1];
    }

    return queryParams;
  };

  var fields = function () {
    return  {
      billing_address_1: "Billing address ",
      billing_address_2: "required",
      billing_city: "Billing City required",
      billing_country: "Billing country required",
      billing_email: "Billing Email required",
      billing_first_name: "Billing fisrtName required",
      billing_last_name: "Billing LastName required",
      billing_phone: "Billing Phone Number required",
      billing_postcode: "Code postal required"
    };
  }
});
