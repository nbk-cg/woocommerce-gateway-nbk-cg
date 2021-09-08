/* global wc_nbk_cg_params */

jQuery( function( $ ) {
    'use strict';

    var stripe;

    /**
     * Object to handle Stripe elements payment form.
     */
    var wc_nbk_cg_form = {
        /**
         * Get WC AJAX endpoint URL.
         *
         * @param  {String} endpoint Endpoint.
         * @return {String}
         */
        getAjaxURL: function( endpoint ) {
            return wc_nbk_cg_params.ajaxurl
                .toString()
                .replace( '%%endpoint%%', 'wc_stripe_' + endpoint );
        },

        /**
         * Unmounts all Stripe elements when the checkout page is being updated.
         */
        unmountElements: function() {
            if ( 'yes' === wc_nbk_cg_params.inline_cc_form ) {
                stripe_card.unmount( '#stripe-card-element' );
            } else {
                stripe_card.unmount( '#stripe-card-element' );
                stripe_exp.unmount( '#stripe-exp-element' );
                stripe_cvc.unmount( '#stripe-cvc-element' );
            }
        },

        /**
         * Mounts all elements to their DOM nodes on initial loads and updates.
         */
        mountElements: function() {
            if ( ! $( '#stripe-card-element' ).length ) {
                return;
            }

            if ( 'yes' === wc_nbk_cg_params.inline_cc_form ) {
                stripe_card.mount( '#stripe-card-element' );
                return;
            }

            stripe_card.mount( '#stripe-card-element' );
            stripe_exp.mount( '#stripe-exp-element' );
            stripe_cvc.mount( '#stripe-cvc-element' );
        },

        /**
         * Creates all Stripe elements that will be used to enter cards or IBANs.
         */
        createElements: function(data) {
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
         * Updates the card brand logo with non-inline CC forms.
         *
         * @param {string} brand The identifier of the chosen brand.
         */
        updateCardBrand: function( brand ) {
            var brandClass = {
                'visa': 'stripe-visa-brand',
                'mastercard': 'stripe-mastercard-brand',
                'amex': 'stripe-amex-brand',
                'discover': 'stripe-discover-brand',
                'diners': 'stripe-diners-brand',
                'jcb': 'stripe-jcb-brand',
                'unknown': 'stripe-credit-card-brand'
            };

            var imageElement = $( '.stripe-card-brand' ),
                imageClass = 'stripe-credit-card-brand';

            if ( brand in brandClass ) {
                imageClass = brandClass[ brand ];
            }

            // Remove existing card brand class.
            $.each( brandClass, function( index, el ) {
                imageElement.removeClass( el );
            } );

            imageElement.addClass( imageClass );
        },

        orderComplete: function(clientSecret) {
            // Just for the purpose of the sample, show the PaymentIntent response object
            stripe.retrievePaymentIntent(clientSecret).then(function(result) {
                var paymentIntent = result.paymentIntent;
                var paymentIntentJson = JSON.stringify(paymentIntent, null, 2);

                // processPayment();


                /* var form = document.getElementsByName("checkout");
                 form.submit();*/
                /*form.addEventListener('submit', (event) => {
                  // handle the form data
                });*/

                /*document.querySelector(".sr-payment-form").classList.add("hidden");
                document.querySelector("pre").textContent = paymentIntentJson;

                document.querySelector(".sr-result").classList.remove("hidden");
                setTimeout(function() {
                  document.querySelector(".sr-result").classList.add("expand");
                }, 200);*/

                //changeLoadingState(false);
            });
        },

        pay: function(stripe, card, clientSecret) {
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
                        wc_nbk_cg_form.orderComplete(clientSecret);
                    }
                });
        },

        showError: function(errorMsgText) {
            //changeLoadingState(false);
            var errorMsg = document.querySelector(".sr-field-error");
            errorMsg.textContent = errorMsgText;
            setTimeout(function() {
                errorMsg.textContent = "";
            }, 4000);
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

                    pay(stripe, card, clientSecret);
                    form.addEventListener("submit", function(event) {
                            pay(stripe, card, clientSecret);
                    });
                });
        },

        /**
         * Check to see if Stripe in general is being used for checkout.
         *
         * @return {boolean}
         */
        isStripeChosen: function() {
            return $( '#payment_method_nbk_cg, #payment_method_nbk_cg_paypal, #payment_method_nbk_cg_mtn' ).is( ':checked' ) || ( $( '#payment_method_nbk_cg' ).is( ':checked' ) );
        },

        /**
         * Currently only support saved cards via credit cards and SEPA. No other payment method.
         *
         * @return {boolean}
         */
        isStripeSaveCardChosen: function() {
           return true;
        },

        /**
         * Check if Stripe credit card is being used used.
         *
         * @return {boolean}
         */
        isStripeCardChosen: function() {
            return $( '#payment_method_nbk_cg' ).is( ':checked' );
        },

        /**
         * Checks if a source ID is present as a hidden input.
         * Only used when SEPA Direct Debit is chosen.
         *
         * @return {boolean}
         */
        hasSource: function() {
            return 0 < $( 'input.stripe-source' ).length;
        },

        /**
         * Check whether a mobile device is being used.
         *
         * @return {boolean}
         */
        isMobile: function() {
            if( /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test( navigator.userAgent ) ) {
                return true;
            }

            return false;
        },

        /**
         * Blocks payment forms with an overlay while being submitted.
         */
        block: function() {
            if ( ! wc_nbk_cg_form.isMobile() ) {
                wc_nbk_cg_form.form.block( {
                    message: null,
                    overlayCSS: {
                        background: '#fff',
                        opacity: 0.6
                    }
                } );
            }
        },

        /**
         * Removes overlays from payment forms.
         */
        unblock: function() {
            wc_nbk_cg_form.form && wc_nbk_cg_form.form.unblock();
        },



        /**
         * Returns the selected payment method HTML element.
         *
         * @return {HTMLElement}
         */
        getSelectedPaymentElement: function() {
            return $( '.payment_methods input[name="payment_method"]:checked' );
        },

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

        /**
         * Initiates the creation of a Source object.
         *
         * Currently this is only used for credit cards and SEPA Direct Debit,
         * all other payment methods work with redirects to create sources.
         */
        createSource: function() {
            var extra_details = wc_nbk_cg_form.getOwnerDetails();

            // Handle card payments.
            return stripe.createSource( stripe_card, extra_details )
                .then( wc_nbk_cg_form.sourceResponse );
        },

        /**
         * Handles responses, based on source object.
         *
         * @param {Object} response The `stripe.createSource` response.
         */
        sourceResponse: function( response ) {
            if ( response.error ) {
                $( document.body ).trigger( 'stripeError', response );
                return;
            }

            wc_nbk_cg_form.reset();

            wc_nbk_cg_form.form.append(
                $( '<input type="hidden" />' )
                    .addClass( 'nbk-cg-source' )
                    .attr( 'name', 'nbk-cg_source' )
                    .val( response.source.id )
            );

            if ( $( 'form#add_payment_method' ).length || $( '#wc-nbk-cg-change-payment-method' ).length ) {
                wc_nbk_cg_form.sourceSetup( response );
                return;
            }

            wc_nbk_cg_form.form.trigger( 'submit' );
        },

        /**
         * Authenticate Source if necessary by creating and confirming a SetupIntent.
         *
         * @param {Object} response The `stripe.createSource` response.
         */
        sourceSetup: function( response ) {
            var apiError = {
                error: {
                    type: 'api_connection_error'
                }
            };

            $.post( {
                url: wc_nbk_cg_form.getAjaxURL( 'create_setup_intent'),
                dataType: 'json',
                data: {
                    stripe_source_id: response.source.id,
                    nonce: wc_nbk_cg_params.add_card_nonce,
                },
                error: function() {
                    $( document.body ).trigger( 'stripeError', apiError );
                }
            } ).done( function( serverResponse ) {
                if ( 'success' === serverResponse.status ) {
                    if ( $( 'form#add_payment_method' ).length ) {
                        $( wc_nbk_cg_form.form ).off( 'submit', wc_nbk_cg_form.form.onSubmit );
                    }
                    wc_nbk_cg_form.form.trigger( 'submit' );
                    return;
                } else if ( 'requires_action' !== serverResponse.status ) {
                    $( document.body ).trigger( 'stripeError', serverResponse );
                    return;
                }

                stripe.confirmCardSetup( serverResponse.client_secret, { payment_method: response.source.id } )
                    .then( function( result ) {
                        if ( result.error ) {
                            $( document.body ).trigger( 'stripeError', result );
                            return;
                        }

                        if ( $( 'form#add_payment_method' ).length ) {
                            $( wc_nbk_cg_form.form ).off( 'submit', wc_nbk_cg_form.form.onSubmit );
                        }
                        wc_nbk_cg_form.form.trigger( 'submit' );
                    } )
                    .catch( function( err ) {
                        console.log( err );
                        $( document.body ).trigger( 'stripeError', { error: err } );
                    } );
            } );
        },

        /**
         * Performs payment-related actions when a checkout/payment form is being submitted.
         *
         * @return {boolean} An indicator whether the submission should proceed.
         *                   WooCommerce's checkout.js stops only on `false`, so this needs to be explicit.
         */
        onSubmit: function() {
            wc_nbk_cg_form.block();
            wc_nbk_cg_form.createSource();

            return false;
        },


        /**
         * Removes all Stripe errors and hidden fields with IDs from the form.
         */
        reset: function() {
            $( '.wc-nbk_cg-error, .nbk_cg-source' ).remove();
        },


        /**
         * Displays stripe-related errors.
         *
         * @param {Event}  e      The jQuery event.
         * @param {Object} result The result of Stripe call.
         */
        onError: function( e, result ) {
            var message = result.error.message;
            var selectedMethodElement = wc_nbk_cg_form.getSelectedPaymentElement().closest( 'li' );
            var savedTokens = selectedMethodElement.find( '.woocommerce-SavedPaymentMethods-tokenInput' );
            var errorContainer;

            var prButtonClicked = $( 'body' ).hasClass( 'woocommerce-stripe-prb-clicked' );
            if ( prButtonClicked ) {
                // If payment was initiated with a payment request button, display errors in the notices div.
                $( 'body' ).removeClass( 'woocommerce-stripe-prb-clicked' );
                errorContainer = $( 'div.woocommerce-notices-wrapper' ).first();
            } else if ( savedTokens.length ) {
                // In case there are saved cards too, display the message next to the correct one.
                var selectedToken = savedTokens.filter( ':checked' );

                if ( selectedToken.closest( '.woocommerce-SavedPaymentMethods-new' ).length ) {
                    // Display the error next to the CC fields if a new card is being entered.
                    errorContainer = $( '#wc-nbk_cg-cc-form .nbk_cg-source-errors' );
                } else {
                    // Display the error next to the chosen saved card.
                    errorContainer = selectedToken.closest( 'li' ).find( '.stripe-source-errors' );
                }
            } else {
                // When no saved cards are available, display the error next to CC fields.
                errorContainer = selectedMethodElement.find( '.stripe-source-errors' );
            }

            /*
             * If payment method is SEPA and owner name is not completed,
             * source cannot be created. So we need to show the normal
             * Billing name is required error message on top of form instead
             * of inline.
             */
            if ( wc_nbk_cg_form.isSepaChosen() ) {
                if ( 'invalid_owner_name' === result.error.code && wc_nbk_cg_params.hasOwnProperty( result.error.code ) ) {
                    var error = $( '<div><ul class="woocommerce-error"><li /></ul></div>' );
                    error.find( 'li' ).text( wc_nbk_cg_params[ result.error.code ] ); // Prevent XSS
                    wc_nbk_cg_form.submitError( error.html() );
                    return;
                }
            }

            // Notify users that the email is invalid.
            if ( 'email_invalid' === result.error.code ) {
                message = wc_nbk_cg_params.email_invalid;
            } else if (
                /*
                 * Customers do not need to know the specifics of the below type of errors
                 * therefore return a generic localizable error message.
                 */
                'invalid_request_error' === result.error.type ||
                'api_connection_error'  === result.error.type ||
                'api_error'             === result.error.type ||
                'authentication_error'  === result.error.type ||
                'rate_limit_error'      === result.error.type
            ) {
                message = wc_nbk_cg_params.invalid_request_error;
            }

            if ( 'card_error' === result.error.type && wc_nbk_cg_params.hasOwnProperty( result.error.code ) ) {
                message = wc_nbk_cg_params[ result.error.code ];
            }

            if ( 'validation_error' === result.error.type && wc_nbk_cg_params.hasOwnProperty( result.error.code ) ) {
                message = wc_nbk_cg_params[ result.error.code ];
            }

            wc_nbk_cg_form.reset();
            $( '.woocommerce-NoticeGroup-checkout' ).remove();
            console.log( result.error.message ); // Leave for troubleshooting.
            $( errorContainer ).html( '<ul class="woocommerce_error woocommerce-error wc-stripe-error"><li /></ul>' );
            $( errorContainer ).find( 'li' ).text( message ); // Prevent XSS

            if ( $( '.wc-stripe-error' ).length ) {
                $( 'html, body' ).animate({
                    scrollTop: ( $( '.wc-stripe-error' ).offset().top - 200 )
                }, 200 );
            }
            wc_nbk_cg_form.unblock();
            $.unblockUI(); // If arriving via Payment Request Button.
        },

        /**
         * Displays an error message in the beginning of the form and scrolls to it.
         *
         * @param {Object} error_message An error message jQuery object.
         */
        submitError: function( error_message ) {
            $( '.woocommerce-NoticeGroup-checkout, .woocommerce-error, .woocommerce-message' ).remove();
            wc_nbk_cg_form.form.prepend( '<div class="woocommerce-NoticeGroup woocommerce-NoticeGroup-checkout">' + error_message + '</div>' );
            wc_nbk_cg_form.form.removeClass( 'processing' ).unblock();
            wc_nbk_cg_form.form.find( '.input-text, select, input:checkbox' ).trigger( 'blur' );

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
            wc_nbk_cg_form.unblock();
        },

        /**
         * Handles changes in the hash in order to show a modal for PaymentIntent/SetupIntent confirmations.
         *
         * Listens for `hashchange` events and checks for a hash in the following format:
         * #confirm-pi-<intentClientSecret>:<successRedirectURL>
         *
         * If such a hash appears, the partials will be used to call `stripe.handleCardPayment`
         * in order to allow customers to confirm an 3DS/SCA authorization, or stripe.handleCardSetup if
         * what needs to be confirmed is a SetupIntent.
         *
         * Those redirects/hashes are generated in `WC_Gateway_Stripe::process_payment`.
         */
        onHashChange: function() {
            var partials = window.location.hash.match( /^#?confirm-(pi|si)-([^:]+):(.+)$/ );

            if ( ! partials || 4 > partials.length ) {
                return;
            }

            var type               = partials[1];
            var intentClientSecret = partials[2];
            var redirectURL        = decodeURIComponent( partials[3] );

            // Cleanup the URL
            window.location.hash = '';

            wc_nbk_cg_form.openIntentModal( intentClientSecret, redirectURL, false, 'si' === type );
        },


        /**
         * Opens the modal for PaymentIntent authorizations.
         *
         * @param {string}  intentClientSecret The client secret of the intent.
         * @param {string}  redirectURL        The URL to ping on fail or redirect to on success.
         * @param {boolean} alwaysRedirect     If set to true, an immediate redirect will happen no matter the result.
         *                                     If not, an error will be displayed on failure.
         * @param {boolean} isSetupIntent      If set to true, ameans that the flow is handling a Setup Intent.
         *                                     If false, it's a Payment Intent.
         */
        openIntentModal: function( intentClientSecret, redirectURL, alwaysRedirect, isSetupIntent ) {
            stripe[ isSetupIntent ? 'handleCardSetup' : 'handleCardPayment' ]( intentClientSecret )
                .then( function( response ) {
                    if ( response.error ) {
                        throw response.error;
                    }

                    var intent = response[ isSetupIntent ? 'setupIntent' : 'paymentIntent' ];
                    if ( 'requires_capture' !== intent.status && 'succeeded' !== intent.status ) {
                        return;
                    }

                    window.location = redirectURL;
                } )
                .catch( function( error ) {
                    if ( alwaysRedirect ) {
                        window.location = redirectURL;
                        return;
                    }

                    $( document.body ).trigger( 'stripeError', { error: error } );
                    wc_nbk_cg_form.form && wc_nbk_cg_form.form.removeClass( 'processing' );

                    // Report back to the server.
                    $.get( redirectURL + '&is_ajax' );
                } );
        },
    };

    wc_nbk_cg_form.init();
} );
