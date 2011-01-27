
function confirmLink(theLink, theSqlQuery)
{
    // Confirmation is not required in the configuration file
    // or browser is Opera (crappy js implementation)
    if (typeof(window.opera) != 'undefined') {
        return true;
    }

    var is_confirmed = confirm("Ты точно хочешь" + ' :\n' + theSqlQuery);
    if (is_confirmed) {
       if ( typeof(theLink.href) != 'undefined' ) {
            theLink.href += '&is_js_confirmed=1';
        } else if ( typeof(theLink.form) != 'undefined' ) {
            theLink.form.action += '?is_js_confirmed=1';
        }
    }

    return is_confirmed;
} // end of the 'confirmLink()' function
