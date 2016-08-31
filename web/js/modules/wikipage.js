function tinymce_toggle(select_id, checkbox_id) {
    var tme = document.getElementById(checkbox_id);
    var sel = document.getElementById(select_id);
    var sel_val = 0;
    var tme_flag = false;
    if(sel) {
        sel_val = sel.value;
        if(tme) {
            if(sel_val!=1) {
                tme.parentNode.style.display = 'none';
            } 
            else {
                tme.parentNode.style.display = 'inline';
            }
        }
    }
    if(tme) {
        tme_flag = tme.checked;
    }
    if(tinyMCE) {
        if (tme_flag && sel_val==1) {
            tinyMCE.init({
                theme: 'advanced',
                mode: 'specific_textareas',
                editor_selector: 'wikieditor',
                plugins: 'fullscreen',
                force_hex_style_colors: true,
                theme_advanced_buttons1: 'bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,|,styleselect,formatselect,fontselect,fontsizeselect',
                theme_advanced_buttons2: 'cut,copy,paste,pastetext,pasteword,|,search,replace,|,bullist,numlist,|,outdent,indent,blockquote,|,undo,redo,|,link,unlink,anchor,image,cleanup,help,code,|,insertdate,inserttime,preview,|,forecolor,backcolor',
                theme_advanced_buttons3: 'tablecontrols,|,hr,removeformat,visualaid,|,sub,sup,|,charmap,iespell,advhr,|,fullscreen',
                theme_advanced_buttons4: 'insertlayer,moveforward,movebackward,absolute,|,styleprops,spellchecker,|,cite,abbr,acronym,del,ins,attribs,|,visualchars,nonbreaking,template,blockquote,pagebreak,|,insertfile,insertimage',
                theme_advanced_toolbar_location: 'top',
                theme_advanced_toolbar_align: 'left',
                theme_advanced_statusbar_location: 'bottom',
                theme_advanced_resizing: true,
                document_base_url: '/article/',
                fullscreen_new_window: true,
                element_format: 'html',
                plugins : 'autolink,lists,spellchecker,pagebreak,style,layer,table,save,advhr,advimage,advlink,emotions,iespell,inlinepopups,insertdatetime,preview,media,searchreplace,print,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras,template',
            });
            tinyMCE.activeEditor.show();
        }
        else {
            tinyMCE.activeEditor.hide();
        }
    }
} 
