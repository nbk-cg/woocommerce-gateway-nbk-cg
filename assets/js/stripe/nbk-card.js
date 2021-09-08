jQuery( function( $ ) {
    'use strict';
    var stripe;
    /**
     * Object to handle Stripe elements payment form.
     */
    var wc_nbk_cg_form = {

        /**
         * Retrieves "owner" data from either the billing fields in a form or preset settings.
         *
         * @return {Object}
         */
        getOwnerDetails: function() {
            var first_name = $( '#billing_first_name' ).length ? $( '#billing_first_name' ).val() : wc_nbk_cg_params.billing_first_name,
                last_name  = $( '#billing_last_name' ).length ? $( '#billing_last_name' ).val() : wc_nbk_cg_params.billing_last_name,
                owner      = { name: '', address: {}, email: '', phone: '' };

            owner.name = first_name;

            if ( first_name && last_name ) {
                owner.name = first_name + ' ' + last_name;
            } else {
                owner.name = $( '#nbk-cg-payment-data' ).data( 'full-name' );
            }

            owner.email = $( '#billing_email' ).val();
            owner.phone = $( '#billing_phone' ).val();

            /* Stripe does not like empty string values so
             * we need to remove the parameter if we're not
             * passing any value.
             */
            if ( typeof owner.phone === 'undefined' || 0 >= owner.phone.length ) {
                delete owner.phone;
            }

            if ( typeof owner.email === 'undefined' || 0 >= owner.email.length ) {
                if ( $( '#nbk-cg-payment-data' ).data( 'email' ).length ) {
                    owner.email = $( '#nbk-cg-payment-data' ).data( 'email' );
                } else {
                    delete owner.email;
                }
            }

            if ( typeof owner.name === 'undefined' || 0 >= owner.name.length ) {
                delete owner.name;
            }

            owner.address.line1       = $( '#billing_address_1' ).val() || wc_nbk_cg_params.billing_address_1;
            owner.address.line2       = $( '#billing_address_2' ).val() || wc_nbk_cg_params.billing_address_2;
            owner.address.state       = $( '#billing_state' ).val()     || wc_nbk_cg_params.billing_state;
            owner.address.city        = $( '#billing_city' ).val()      || wc_nbk_cg_params.billing_city;
            owner.address.postal_code = $( '#billing_postcode' ).val()  || wc_nbk_cg_params.billing_postcode;
            owner.address.country     = $( '#billing_country' ).val()   || wc_nbk_cg_params.billing_country;

            return {
                owner: owner,
            };
        },

        setupElements: function(data) {
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
        },

        /**
         * Initialize event handlers and UI state.
         */
        init: function() {
            // Initialize tokenization script if on change payment method page and pay for order page.
            if ( 'yes' === wc_nbk_cg_params.is_change_payment_page || 'yes' === wc_nbk_cg_params.is_pay_for_order_page ) {
                $( document.body ).trigger( 'wc-credit-card-form-init' );
            }

            // checkout page
            if ( $( 'form.woocommerce-checkout' ).length ) {
                this.form = $( 'form.woocommerce-checkout' );
            }

            $( 'form.woocommerce-checkout' )
                .on(
                    'checkout_place_order_nbk_cg ',
                    this.onSubmit
                );

            // pay order page
            if ( $( 'form#order_review' ).length ) {
                this.form = $( 'form#order_review' );
            }

            $( 'form#order_review, form#add_payment_method' )
                .on(
                    'submit',
                    this.onSubmit
                );

            $( 'form.woocommerce-checkout' )
                .on(
                    'change',
                    this.reset
                );

            $( document )
                .on(
                    'stripeError',
                    this.onError
                )
                .on(
                    'checkout_error',
                    this.reset
                );

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
                    //var form = document.getElementById("payment-form");

                    var form = document.querySelector("form[name='checkout']");
                    //form = document.getElementsByClassName("woocommerce-checkout");

                    // pay(stripe, card, clientSecret);
                    form.addEventListener("submit", function(event) {
                        // pay(stripe, card, clientSecret);
                    });
                });
        },
    }
    wc_nbk_cg_form.init();
} );
