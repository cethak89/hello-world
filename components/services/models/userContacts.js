/**
 * Created by furkan on 08.04.2015.
 */

var userContactModule = angular.module('userContactModule', []);

userContactModule.service('userContact',['$http',function($http)
{
    $http.defaults.headers.post["Content-Type"] = "application/json";

    var contacts= [];

    var updateContactInfo = function(contactNewInfo,districtName){

        for(var i in contacts){
            if(contacts[i].id === contactNewInfo.contact_id){
                contacts[i] = {
                    address: contactNewInfo.contact_address,
                    district: districtName,
                    icon_id: contacts[i].icon_id,
                    id: contacts[i].id,
                    mobile: contactNewInfo.contact_mobile,
                    name: contactNewInfo.contact_name,
                    count: contacts[i].count
                };
                break;
            }
        }
    };

    var removeContact = function(contactId){
        var i=0;
        for(; i < contacts.length; i++){
            if(contacts[i].id === contactId){
                break;
            }
        }

        if(i < contacts.length)
            contacts.splice(i,1);
    };

    var initContacts = function(access_token, callbackFunc){
        var data = {
            access_token : access_token
        };
        $http.post(webServer +'/user-contact-list',data).success(function (response) {
            contacts = response;

            contacts.forEach(function(contact){
                contact.displayName = contact.name + " " + contact.surname;
            });
            if(callbackFunc)
                callbackFunc(true);
        }).error(function(data, status) {
            if(callbackFunc)
                callbackFunc(false);
        });
    };

    return{
        getContacts : function() {
            return contacts;
        },
        setContacts : function(values) {
            contacts = values;
        },
        initContacts : function(access_token, callbackFunc){
            initContacts(access_token,callbackFunc);
        },
        addContact : function(districtId,districtName, contactName,/* contactSurname, */contactAddress, contactMobile, access_token, callbackFunction){
            var contactInfo = {
                "city_id" :districtId,
                "contact_address" : contactAddress,
                "contact_mobile" : contactMobile,
                "access_token" : access_token,
                "contact_name" : contactName
            };

            $http.post(webServer + '/user-set-contact-list',contactInfo).success(function (data)
            {
                initContacts(access_token);
                callbackFunction(true,data.id);
            }).error( function(data)
            {
                callbackFunction(false, data.description);
            });
        },
        updateContact: function(districtId,districtName,contactId, contactName,/* contactSurname,*/ contactAddress, contactMobile, access_token, callbackFunction){
            var contactInfo = {
                city_id : districtId,
                contact_name : contactName,
                contact_address : contactAddress,
                contact_mobile : contactMobile,
                access_token : access_token,
                contact_id: contactId
            };

            $http.post(webServer+ '/user-update-contact-list',contactInfo).success(function (data)
            {
                updateContactInfo(contactInfo,districtName);

                callbackFunction(true);
            }).error( function(data)
            {
                callbackFunction(false, data.description);
            });
        },
        removeContact: function(contactId, access_token, callbackFunction){
            var contactInfo = {
                contact_id : contactId,
                access_token : access_token
            };

            $http.post(webServer+ '/user-delete-contact-list',contactInfo).success(function (data)
            {
                removeContact(contactId);

                callbackFunction(true);
            }).error( function(data)
            {
                callbackFunction(false, data.description);
            });
        },
        setContactPurchase : function(contactId){
            var i=0;
            for(; i < contacts.length; i++){
                if(contacts[i].id === contactId){
                    break;
                }
            }

            if(i < contacts.length)
                contacts[i].count = contacts[i].count + 1;
        }
    }
}]);