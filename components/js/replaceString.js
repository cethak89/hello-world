/**
 * Created by furkan on 03.09.2015.
 */


var replaceString = function(string, removedCharSet, usedCharSet){
    var newString = (string) ? string.toString().trim().replace(removedCharSet, usedCharSet) : null;
    return newString;
};