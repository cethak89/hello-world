'use strict';

/**
 * @ngdoc service
 * @name app.purchaseModel
 * @description
 * # purchaseModel
 * Service in the bloomNFresh.
 */
angular.module('app')
    .service('purchaseFlowerModel', function ($cookies, flowerFactory) {

        var flower = {};

        this.setFlower = function (flowerObj) {
            flower = jQuery.extend(true, {}, flowerObj);
            flower.price = parseFloat(replaceString(flower.price, ",", "."));
            //flower.newPrice = flower.price / 100 * 118;

            if( flower.product_type == 2 ){
                flower.newPrice = flower.price / 100 * 101;
            }
            else if( flower.product_type == 3 ){
                flower.newPrice = flower.price / 100 * 118;
            }
            else{
                flower.newPrice = flower.price / 100 * 108;
            }

            $cookies.putObject('sendingFlower', {
                id: flower.id,
                sendingDistrict: flower.sendingDistrict,
                price: flower.price,
                newPrice: flower.newPrice
            });
        };

        this.getFlower = function () {
            if (flower.id === undefined) {
                var storedFlowerObj = $cookies.getObject('sendingFlower');
                if(storedFlowerObj){
                    var flowerObj = flowerFactory.getFlowerWithCity(storedFlowerObj, storedFlowerObj.sendingDistrict.city_id );

                    flower = jQuery.extend(true, {}, flowerObj);
                    flower.sendingDistrict = storedFlowerObj.sendingDistrict;
                    flower.price = storedFlowerObj.price;
                    flower.newPrice = storedFlowerObj.newPrice;
                    flower.priceWithDiscount = storedFlowerObj.priceWithDiscount;
                }
            }

            return flower;
        };

        this.setDiscountedPrice = function (newFlowerPrice) {
            flower.priceWithDiscount = newFlowerPrice;
            flower.newPrice = flower.priceWithDiscount / 100 * 118;

            if( flower.product_type == 2 ){
                flower.newPrice = flower.priceWithDiscount / 100 * 101;
            }
            else if( flower.product_type == 3 ){
                flower.newPrice = flower.priceWithDiscount / 100 * 118;
            }
            else{
                flower.newPrice = flower.priceWithDiscount / 100 * 108;
            }

            $cookies.putObject('sendingFlower', {
                id: flower.id,
                sendingDistrict: flower.sendingDistrict,
                price: flower.price,
                newPrice: flower.newPrice,
                priceWithDiscount : flower.priceWithDiscount
            });
        }
    });
