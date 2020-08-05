'use strict';

/**
 * @ngdoc service
 * @name app.sessioncamHelper
 * @description
 * # kissmetricsHelper
 * Service in the app.
 */

//angular.module('app')
//    .service('kissmetricsHelper', function ($window) {
//        this.sendPageView = function(pageName){
//            _kmq.push(['record', pageName + " görüntüledi"]);
//        };
//
//        this.productViewed = function(productId, productName, productPrice){
//            _kmq.push([
//                'record', 'Ürün görüntüledi',
//                {
//                    'displayed_product_id' : productId,
//                    'displayed_product_name' : productName,
//                    'displayed_product_price' : productPrice
//                }
//            ]);
//        };
//
//        this.clickEvent = function(clickName, clickDescription){
//            _kmq.push(['trackClick', clickName, clickDescription]);
//        };
//
//        this.outboundClickEvent = function(clickName, clickDescription){
//            _kmq.push(['trackClickOnOutboundLink', clickName, clickDescription]);
//        };
//
//        this.recordEvent = function(EventName){
//            _kmq.push(['record', EventName]);
//        };
//
//        this.productAdded = function(flowerId, flowerName, flowerPrice){
//            _kmq.push([
//                'record', 'Sepete ekledi',
//                {
//                    'added_product_id' : flowerId,
//                    'added_product_name' : flowerName,
//                    'added_product_price' : flowerPrice
//                }
//            ]);
//        };
//
//        this.purchased = function(orderId, purchaseId, purchaseName, purchasePrice,usedCouponName){
//            var usedCoupon = usedCouponName !== null ? usedCouponName : "";
//
//            _kmq.push([
//                'record', 'Purchased',
//                {
//                    'order_id': orderId,
//                    'purchased_product_id' : purchaseId,
//                    'purchased_product_name' : purchaseName,
//                    'checkout_price' : purchasePrice,
//                    'used_coupon': usedCoupon
//                }
//            ]);
//        };
//
//        this.usedCoupon = function(couponName, couponValue, couponType){
//            var typeString = '';
//
//            switch (couponType){
//                case "1": {
//                    typeString = '-';
//                    break;
//                }
//                case "2": {
//                    typeString = '%';
//                    break;
//                }
//                default :{
//                    typeString = '%';
//                }
//            }
//
//            _kmq.push([
//                'record', 'Kupon Kullandı',
//                {
//                    'kupon ismi': couponName,
//                    'kupon değeri': typeString + couponValue
//                }
//            ]);
//        };
//
//        this.registered = function(registrationType, email){
//            _kmq.push(['identify', email ]);
//            _kmq.push(['record', 'Kayıt Oldu', {'kayıt türü': registrationType} ]);
//        };
//
//        this.loggedIn = function(loginType, email){
//            _kmq.push(['identify', email ]);
//            _kmq.push(['record', 'Giriş Yaptı', {'giriş türü': loginType} ]);
//        }
//    });
