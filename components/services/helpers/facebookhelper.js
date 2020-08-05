'use strict';

/**
 * @ngdoc service
 * @name app.facebookhelper
 * @description
 * # facebookhelper
 * Service in the bloomNFresh.
 */
angular.module('app')
  .service('facebookhelper', function ($window) {
        this.facebookAdTypes = {
            VIEW_CONTENT : 'ViewContent',
            SEARCH : 'Search',
            ADD_TO_CART : 'AddToCart',
            ADD_TO_WISH_LIST : 'AddToWishlist',
            ADD_PAYMENT_INFO : 'AddPaymentInfo',
            INITIATE_CHECKOUT : 'InitiateCheckout',
            PURCHASE : 'Purchase',
            LEAD : 'Lead',
            REGISTRATION : 'CompleteRegistration'
        };

        this.trackEvent = function(eventName, eventObj){
            $window.fbq('track', eventName, eventObj);
        }
  });
