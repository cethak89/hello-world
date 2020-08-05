/**
 * Created by furkan on 11.05.2015.
 */

var userCampaignsModule = angular.module('userCampaignsModule', []);

userCampaignsModule.service('userCampaigns',function($http,translateHelper)
{
    var campaigns = [];

    var
        initCampaigns = function(access_token, callbackFunc){
        var data = {
            access_token : access_token,
            lang_id : translateHelper.getCurrentLang()
        };

        $http.post(webServer +'/user-get-coupon-list',data).success(function (response) {
            campaigns = response.coupon_list;
            if(callbackFunc)
                callbackFunc(true);
        }).error(function(data, status) {
            if(callbackFunc)
                callbackFunc(false);
        });
    };

    return {
        getCampaigns : function() {
            return campaigns;
        },
        initCampaigns : function(access_token, callbackFunc){
            initCampaigns(access_token,callbackFunc);
        },
        addCampaign : function(campaignId, access_token, callbackFunction){
            var campaignInfo = {
                "coupon_id" :campaignId,
                "access_token" : access_token,
                "lang_id" : translateHelper.getCurrentLang()
            };

            $http.post(webServer + '/user-set-coupon',campaignInfo).success(function (data)
            {
                campaigns = data.couponList;
                //initCampaigns(access_token);
                callbackFunction(true,data);
            }).error( function(data)
            {
                callbackFunction(false, data.description);
            });
        },
        getSuitableCampaigns: function(flowerId){
            var suitableCampaigns = [];

            for(var i in campaigns){
                if(campaigns[i].flowers === undefined)
                    suitableCampaigns.push(campaigns[i]);
                else{
                    var flowers = campaigns[i].flowers;
                    for(var j in flowers){
                        if(flowers[j].id === flowerId)
                        {
                            suitableCampaigns.push(campaigns[i]);
                        }
                    }
                }
            }
            //console.log(suitableCampaigns);
            return suitableCampaigns;
        },
        getSuitableCampaignsDate: function(flowerId , startDate){
            var suitableCampaigns = [];

            for(var i in campaigns){
                if(campaigns[i].flowers === undefined)
                    suitableCampaigns.push(campaigns[i]);
                else{
                    var flowers = campaigns[i].flowers;
                    for(var j in flowers){
                        if(flowers[j].id === flowerId)
                        {
                            //console.log(flowers[j]);
                            //console.log( flowers[j].limit_start +  ' _ ' + startDate.value);
                            if(flowers[j].limit_start < startDate && flowers[j].limit_end > startDate ){
                                suitableCampaigns.push(campaigns[i]);
                            }
                        }
                    }
                }
            }
            //campaigns = suitableCampaigns;
            //console.log(suitableCampaigns);
            return suitableCampaigns;
        },
        getBulgariCouponOnly: function(flowerId, access_token){
            var suitableCampaigns = [];
            initCampaigns(access_token);
            var data = {
                access_token : access_token,
                lang_id : translateHelper.getCurrentLang()
            };
            $http.post(webServer +'/user-get-coupon-list',data).success(function (response) {
                campaigns = response.coupon_list;
            });
            for(var i in campaigns){
                var flowers = campaigns[i].flowers;
                for(var j in flowers){
                    if(flowers[j].id === flowerId)
                    {
                        suitableCampaigns.push(campaigns[i]);
                    }
                }
            }
            //campaigns = suitableCampaigns;
            return suitableCampaigns;
        }
    };
});