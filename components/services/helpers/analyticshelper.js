'use strict';

/**
 * @ngdoc service
 * @name app.analyticsHelper
 * @description
 * # analyticsHelper
 * Service in the app.
 */
angular.module('app')
  .service('analyticsHelper', function ($rootScope, $state,  $window, deviceDetector) {
        var pageViewSended = false;

        var deviceType = deviceDetector.whichPlatform();

        this.setpageViewSended = function(value){
            pageViewSended = value;
        };

        this.isPageViewSended = function(){
            return pageViewSended;
        };

        this.sendPageView = function (pageUrl) {
            $window.ga('send', 'pageview',
                {
                    page : pageUrl,
                    title : $rootScope.title
                });
        };

        this.sendCriteoFlowerDetail = function(id , mail, city_id){
            $window.criteo_q = $window.criteo_q || [];
            $window.criteo_q.push(
                { event: "setAccount", account: 27862 },
                { event: "setEmail", email: mail },
                { event: "setSiteType", type: deviceType },
                { event: "viewItem", item: id },
                { event: "viewItem", user_segment: city_id }
            );
        };

        this.sendCriteoFlowerList = function(mail, city_id){
            $window.criteo_q = $window.criteo_q || [];
            $window.criteo_q.push(
                { event: "setAccount", account: 27862 },
                { event: "setEmail", email: mail },
                { event: "setSiteType", type: deviceType },
                { event: "viewList", item:[ 9 , 2 , 37 ]},
                { event: "viewList", user_segment: city_id }
            );
        };

        this.sendCriteoBucket = function(tempId , tempPrice, mail, city_id){
            $window.criteo_q = $window.criteo_q || [];
            $window.criteo_q.push(
                { event: "setAccount", account: 27862 },
                { event: "setEmail", email: mail },
                { event: "setSiteType", type: deviceType },
                { event: "viewBasket", item: [
                    { id: tempId, price: tempPrice, quantity: 1 }
                ]},
                { event: "viewBasket", user_segment: city_id }
            );
        };

        this.sendCriteoSuccessSale = function( salesId ,tempId , tempPrice, mail, city_id){
            $window.criteo_q = $window.criteo_q || [];
            $window.criteo_q.push(
                { event: "setAccount", account: 27862 },
                { event: "setEmail", email: mail },
                { event: "setSiteType", type: deviceType },
                { event: "trackTransaction", id: salesId, item: [
                    { id: tempId, price: tempPrice, quantity: 1 }
                ]},
                { event: "trackTransaction", user_segment: city_id }
            );
        };

        this.sendCriteoLanding = function(mail, city_id){
            window.criteo_q = window.criteo_q || [];
            window.criteo_q.push(
                { event: "setAccount", account: 27862 },
                { event: "setEmail", email: mail },
                { event: "setSiteType", type: deviceType },
                { event: "viewHome", user_segment: city_id }
            );
        };

        this.sendEvent = function(category, action){
            $window.ga('send', 'event', category, action);
        };


        this.addImpression = function(productId, productName, category){
            //console.log(category);
            $window.ga('ec:addImpression',{
                'id': productId,
                'name': productName,
                'category': category
            } );
            $window.ga('ec:addProduct',{
                'id': productId,
                'name': productName,
                'category': category
            } );
            $window.ga('ec:setAction', 'detail');
        };

        this.addProduct = function(productId, productName,productPrice, category){
            //console.log(category);
            $window.ga('ec:addProduct',{
                'id': productId,
                'name': productName,
                'price': productPrice,
                'category': category,
                'quantity': 1
            } );

            $window.ga('ec:setAction', 'add');
            $window.ga('send', 'event', 'basket', 'add', "Sepete ürün ekledi");
        };

        this.clickEvent = function(label){
            $window.ga('ec:setAction', 'click');

            $window.ga('send', 'event', 'product', 'click', label);
        };

        this.clickRibbonMenu = function(){
            $window.ga('ec:setAction', 'click');

            $window.ga('send', 'event', 'ribbonMenu', 'click', 'ribbonMenu');
        };


        this.sendCheckoutAction = function(step,option){
            $window.ga('ec:setAction','checkout', {
                'step': step,
                'option': option
            });
        };

        this.sendPurchaseAction = function(orderId,productId,productName,productPrice, price, tax, category){
            $window.ga('ec:addProduct',{
                'id': productId,
                'name': productName,
                'price': price / 118 * 100,
                'category' : category,
                'quantity': 1
            } );

            $window.ga('ec:setAction', 'purchase', {
                'id': orderId,
                'revenue': price,
                'tax': tax,
                'shipping': '0'
            });
        }
  });
