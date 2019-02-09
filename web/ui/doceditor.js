define('doceditor', [
    'external/vue',
    'core/listproxy',
    'core/dialog',
    'text!doceditor.html',

    'field/Selector',
    'field/SourcedLabel',
    'field/FirmSelector',
    'field/FilteredSelector',
    'field/AgentSelector',
    'field/DocHeader/PriceItem',
    'doceditor/DeliveryEditor',
    'css!doceditor'
], function(Vue, listproxy, dialog, docEditorTemplate) {

    listproxy.prefetch(['agent.shortlist', 'firm.listnames', 'mybank.shortlist', 'store.shortlist', 'price.listnames', 'deliverytype.listnames',
        'deliveryregion.getlist', 'deliveryregion.listnames', 'worker.listnames']);

    var doceditor = function(doc_container_id, menu_container_id) {
        var doc = new Object;
        var container = document.getElementById(doc_container_id);
        var left_block, main_block, v_separator;
        //var cache = getCacheObject();
        var hltimer;
        var mim = mainInternalMenu();

        function clearHighlight() {
            left_block.style.backgroundColor = '';
        }

        function onLoadError(response, data) {
            if (response.errortype == 'AccessException') {
                if (response.object == 'document' && response.action == 'cancel') {
                    jAlert(response.errormessage + "<br><br>Вы можете <a href='#' onclick=\"return petitionMenu(event, '" + doc.id + "')\"" +
                        ">попросить руководителя</a> выполнить отмену этого документа.", "Не достаточно привилегий!", null, 'icon_err');
                    doc.updateMainMenu();
                }
                else {
                    alert(response.errormessage);
                    doc.updateMainMenu();
                }
            }
            else if (response.errortype == 'InvalidArgumentException') {
                jAlert("Ошибка:\n" + response.errortype + "\nСообщение:" + response.errormessage);
            }
            else {
                alert("Общая ошибка:\n" + response.errortype + "\nСообщение:" + response.errormessage);
                doc.updateMainMenu();
            }
        }

        function onLoadSuccess(response) {
            if (response.object == 'document') {
                if (response.action == 'get') {
                    doc.data = response.content;
                    doc.fillHeader(response.content.header);
                    doc.fillBody(response.content);
                    doc.updateMainMenu();
                }
                else if (response.action == 'update') {
                    left_block.style.backgroundColor = '#cfc';
                    if (hltimer) {
                        window.clearTimeout(hltimer);
                    }
                    hltimer = window.setTimeout(clearHighlight, 500);
                    if (response.content.header) {
                        doc.header = response.content.header;
                    }
                }
                else if (response.action == 'apply' || response.action == 'cancel') {
                    doc.header = response.content.header;
                    //doc.updateMainMenu();
                    updateStoreView();
                    alert('Документ успешно ' + ((response.action == 'apply') ? 'проведён' : 'отменён'));
                }
                else if (response.action == 'markfordelete') {
                    doc.header.mark_del = response.content.result;
                    doc.updateMainMenu();
                    alert('Документ помечен на удаление');
                }
                else if (response.action == 'unmarkdelete') {
                    doc.header.mark_del = 0;
                    doc.updateMainMenu();
                    alert('Отметка об удалении снята');
                }
                else {
                    alert('document:action: ' + response.action);
                }
            }
            else alert("Обработчик не задан:\n" + response);
        }

        function updateStoreView() {
            var store_view = document.getElementById("storeview_container");
            var poslist = document.getElementById('poslist');
            var pladd = document.getElementById('pladd');
            if (store_view) {
                if (doc.header.ok == 0) {
                    store_view.style.display = 'block';
                    poslist.editable = 1;
                    poslist.refresh();
                    pladd.style.display = 'table-row';
                }
                else {
                    store_view.style.display = 'none';
                    pladd.style.display = 'none';
                    poslist.editable = 0;
                    poslist.refresh();
                }
            }
        }

        doc.init = function (doc_id) {
            doc.id = doc_id;
            container.innerHTML = '';
            container.doc = doc;
            left_block = newElement('div', container, '', '');
            left_block.id = 'doc_left_block';
            mm_api.document.get({id: doc_id}, onLoadSuccess, onLoadError);
            v_separator = newElement('div', container, '', '');
            v_separator.id = 'doc_v_separator';
            main_block = newElement('div', container, '', '');
            main_block.id = 'doc_main_block';
            v_separator.addEventListener('click', leftBlockToggle, false);
            if (supports_html5_storage()) {
                if (localStorage['doc_left_block_hidden'] == 'hidden') {
                    lb_hide();
                }
            }

            function lb_show() {
                left_block.style.display = '';
                main_block.style.marginLeft = main_block.oldmargin;
                v_separator.style.backgroundImage = "url('/img/left_separator.png')";

            };

            function lb_hide() {
                left_block.style.display = 'none';
                main_block.oldmargin = main_block.style.marginLeft;
                main_block.style.marginLeft = 10 + "px";
                v_separator.style.backgroundImage = "url('/img/right_separator.png')";

            }

            function leftBlockToggle() {
                var state;
                if (left_block.style.display != 'none') {
                    lb_hide();
                    state = 'hidden';
                }
                else {
                    lb_show();
                    state = 'show';
                }
                if (supports_html5_storage()) {
                    localStorage['doc_left_block_hidden'] = state;
                }
            }

            history.replaceState({doc_id: doc_id}, null, window.href);
        };

        doc.fillBody = function (content) {
            if (content.pe_config) {
                var poseditor_div = newElement('div', main_block, '', '');
                poseditor_div.id = 'poseditor_div';
                var storeview_container = newElement('div', main_block, '', '');
                storeview_container.id = 'storeview_container';
                var poseditor = PosEditorInit(content.pe_config);
            }

        };

        doc.fillHeader = function (data) {
            var tmp;
            doc.header = data;

            doc.head_form = newElement('div', left_block, '', '');
            var vueData = {
                header: doc.header,
                headerFields: data.header_fields,
                extFields: data.ext_fields,
                firmFilters: {
                    bank: false,
                    store: false,
                    cashbox: false
                }
            };

            function showDialog() {
                var config = {
                    header: 'Информация о доставке',
                    component: 'DocEditor-DeliveryEditor',
                    value: {
                        type: doc.header.delivery,
                        date: doc.header.delivery_date,
                        region: doc.header.delivery_region,
                        address: doc.header.delivery_address
                    },
                    callback: function (value) {
                        var fstruct = {
                            id: doc.id,
                            delivery: value.type,
                            delivery_region: value.region,
                            delivery_date: value.date,
                            delivery_address: value.address,
                        };
                        mm_api.document.update(fstruct, onLoadSuccess, onLoadError);
                        vueData.header.delivery = value.type;
                        vueData.header.delivery_date = value.date;
                        vueData.header.delivery_region = value.region;
                        vueData.header.delivery_address = value.address;

                    }
                };
                dialog(config);
            }

            var vueHead = new Vue({
                el:  doc.head_form,
                data: vueData,
                template: docEditorTemplate,
                created: function() {
                    this.save = debounce(this._save, 1000);
                },
                methods: {
                    _save: function() {
                        mm_api.document.update(this.header, onLoadSuccess, onLoadError);
                    },
                    onDeliveryClick: function(event) {
                        showDialog(event);
                    }
                }
            });
            v_separator.style.height = Math.max(left_block.clientHeight, main_block.clientHeight) + "px";
        };

        doc.updateMainMenu = function () {
            // Убираем старые
            mim.contextPanel.clear();
            doc.contextPanel = new Object;
            // История
            doc.contextPanel.hist = mim.contextPanel.addButton({
                icon: "i_log.png",
                caption: "История изменений документа",
                link: "/doc.php?mode=log&doc=" + doc.id
            });
            if (doc.header.mark_del > 0) {
                doc.contextPanel.del = mim.contextPanel.addButton({
                    icon: "i_trash_undo.png",
                    caption: "Отменить удаление",
                    accesskey: "U",
                    onclick: doc.unMarkDelete
                });
            }
            else if (doc.header.ok > 0) {
                doc.contextPanel.cancel = mim.contextPanel.addButton({
                    icon: "i_revert.png",
                    caption: "Отменить проведение документа",
                    accesskey: "С",
                    onclick: doc.cancel
                });
            }
            else {
                doc.contextPanel.apply = mim.contextPanel.addButton({
                    icon: "i_ok.png",
                    caption: "Провести документ",
                    accesskey: "A",
                    onclick: doc.apply
                });
                doc.contextPanel.del = mim.contextPanel.addButton({
                    icon: "i_trash.png",
                    caption: "Отметьть для удаления",
                    accesskey: "D",
                    onclick: doc.markForDelete
                });
            }

            mim.contextPanel.addSeparator();

            doc.contextPanel.print = mim.contextPanel.addButton({
                icon: "i_print.png",
                caption: "Печатные формы",
                accesskey: "P",
                onclick: doc.printForms
            });

            doc.contextPanel.sendFaxForm = mim.contextPanel.addButton({
                icon: "i_fax.png",
                caption: "Отправить по факсу",
                onclick: doc.faxForms
            });

            doc.contextPanel.sendEmailForm = mim.contextPanel.addButton({
                icon: "i_mailsend.png",
                caption: "Отправить по email",
                onclick: doc.emailForms
            });

            mim.contextPanel.addSeparator();

            doc.contextPanel.connect = mim.contextPanel.addButton({
                icon: "i_conn.png",
                caption: "Подчинить",
                onclick: doc.subordinateDialog
            });

            doc.contextPanel.morphto = mim.contextPanel.addButton({
                icon: "i_to_new.png",
                caption: "Создать подчинённый документ",
                onclick: doc.morphToMenu
            });

            mim.contextPanel.addSeparator();

            doc.contextPanel.refillNomenclature = mim.contextPanel.addButton({
                icon: "i_addnom.png",
                caption: "Перезаполнить номенклатуру",
                onclick: doc.refillNomenclatureForm
            });

            if (doc.header.typename == 'zayavka') {
                if (doc.header.reserved == 0) {
                    doc.contextPanel.reserves = mim.contextPanel.addButton({
                        icon: "22x22/object-unlocked.png",
                        caption: "Разрешить резервы",
                        onclick: doc.reservesToggle
                    });
                }
                else {
                    doc.contextPanel.reserves = mim.contextPanel.addButton({
                        icon: "22x22/object-locked.png",
                        caption: "Снять резервы",
                        onclick: doc.reservesToggle
                    });
                }
            }
        };

        doc.apply = function (event) {
            event.preventDefault();
            mm_api.document.apply({id: doc.id}, onLoadSuccess, onLoadError);
            if (doc.contextPanel.apply) {
                mim.contextPanel.updateButton(doc.contextPanel.apply, {
                    icon: "icon_load.gif",
                    caption: "Проведение...",
                    accesskey: "",
                    onclick: function () {
                    }
                });
            }
            return false;
        };

        doc.cancel = function (event) {
            event.preventDefault();
            mm_api.document.cancel({id: doc.id}, onLoadSuccess, onLoadError);
            if (doc.contextPanel.cancel) {
                mim.contextPanel.updateButton(doc.contextPanel.cancel, {
                    icon: "icon_load.gif",
                    caption: "Отмена...",
                    accesskey: "",
                    onclick: function () {
                    }
                });
            }
        };

        doc.markForDelete = function (event) {
            event.preventDefault();
            mm_api.document.markForDelete({id: doc.id}, onLoadSuccess, onLoadError);
            if (doc.contextPanel.del) {
                mim.contextPanel.updateButton(doc.contextPanel.del, {
                    icon: "icon_load.gif",
                    caption: "Ставим пометку...",
                    accesskey: "",
                    onclick: function () {
                    }
                });
            }
        };

        doc.unMarkDelete = function () {
            event.preventDefault();
            mm_api.document.unMarkDelete({id: doc.id}, onLoadSuccess, onLoadError);
            if (doc.contextPanel.del) {
                mim.contextPanel.updateButton(doc.contextPanel.del, {
                    icon: "icon_load.gif",
                    caption: "Снимаем пометку...",
                    accesskey: "",
                    onclick: function () {
                    }
                });
            }
        };

        doc.reservesToggle = function (event) {
            event.preventDefault();
            if (doc.contextPanel.reserves) {
                mim.contextPanel.updateButton(doc.contextPanel.reserves, {
                    icon: "icon_load.gif",
                    caption: "Переключение...",
                    accesskey: "",
                    onclick: function () {
                    }
                });
            }
            var fstruct = {id: doc.id, reserved: doc.header.reserved ? 0 : 1};
            mm_api.document.update(fstruct, onLoadSuccess, onLoadError);
        }

        doc.printForms = function (event) {
            event.preventDefault();
            var menu = CreateContextMenu(event);

            function pickItem(event) {
                var fname = event.target.fname;
                menu.parentNode.removeChild(menu);
                var data = {id: doc.id, name: fname};
                window.location = "/api.php?object=document&action=getprintform&data=" + encodeURIComponent(JSON.stringify(data));
            }

            function onLoadPFLError(response, data) {
                jAlert(response.errortype + ': ' + response.errormessage, 'Печать', null, 'icon_err');
                menu.parentNode.removeChild(menu);
            }

            function onLoadPFLSuccess(response) {
                menu.innerHTML = ''
                var printforms = response.content.printforms;
                for (var i = 0; i < printforms.length; i++) {
                    var elem = document.createElement('div');
                    if (printforms[i].mime) {
                        var mime = printforms[i].mime.replace('/', '-');
                        elem.style.backgroundImage = "url('/img/mime/22/" + mime + ".png')";
                    }
                    elem.innerHTML = printforms[i].desc;
                    elem.fname = printforms[i].name;
                    elem.onclick = pickItem;
                    menu.appendChild(elem);
                }
            }

            mm_api.document.getPrintFormList({id: doc.id}, onLoadPFLSuccess, onLoadPFLError);

            return false;
        }

        doc.faxForms = function (event) {
            event.preventDefault();
            var menu = CreateContextMenu(event);

            function pickItem(event) {
                var fname = event.target.fname;

                menu.innerHTML = '';
                menu.morphToDialog();
                var elem = document.createElement('div');
                elem.innerHTML = 'Номер факса:<br><small>В международном формате +XXXXXXXXXXX...<br>без дефисов, пробелов, и пр.символов</small>';
                menu.appendChild(elem);
                var ifax = document.createElement('input');
                ifax.type = 'text';
                //ifax.value = fax_number;
                ifax.style.width = '200px';
                menu.appendChild(ifax);
                ifax.onkeyup = function () {
                    var regexp = /^\+\d{8,15}$/;
                    if (!regexp.test(ifax.value)) {
                        ifax.style.color = "#f00";
                        bsend.disabled = true;
                    } else {
                        ifax.style.color = "";
                        bsend.disabled = false;
                    }
                };

                elem = document.createElement('br');
                menu.appendChild(elem);
                var bcancel = document.createElement('button');
                bcancel.innerHTML = 'Отменить';
                bcancel.onclick = function () {
                    menu.parentNode.removeChild(menu);
                };
                menu.appendChild(bcancel);
                var bsend = document.createElement('button');
                bsend.innerHTML = 'Отправить';
                menu.appendChild(bsend);
                bsend.onclick = function () {
                    mm_api.document.sendFax({
                        id: doc.id,
                        faxnum: ifax.value,
                        name: fname
                    }, onLoadPFLSuccess, onLoadPFLError);
                    menu.innerHTML = '<img src="/img/icon_load.gif" alt="отправка">Отправка факса...';
                };
                ifax.onkeyup();
            }

            function onLoadPFLError(response, data) {
                jAlert(response.errortype + ': ' + response.errormessage, 'Отправка факса', null, 'icon_err');
                menu.parentNode.removeChild(menu);
            }

            function onLoadPFLSuccess(response) {
                if (response.action == 'getprintformlist') {
                    menu.innerHTML = '';
                    var printforms = response.content.printforms;
                    for (var i = 0; i < printforms.length; i++) {
                        var elem = document.createElement('div');
                        if (printforms[i].mime) {
                            var mime = printforms[i].mime.replace('/', '-');
                            elem.style.backgroundImage = "url('/img/mime/22/" + mime + ".png')";
                        }
                        elem.innerHTML = printforms[i].desc;
                        elem.fname = printforms[i].name;
                        elem.onclick = pickItem;
                        menu.appendChild(elem);
                    }
                }
                else if (response.action == 'sendfax') {
                    jAlert('Факс успешно отправлен на сервер! Вы получите уведомление по email c результатом отправки получателю!', "Выполнено");
                    menu.parentNode.removeChild(menu);
                }
                else {
                    jAlert("Обработка полученного сообщения не реализована", "Отправка факса", null, 'icon_err');
                    menu.parentNode.removeChild(menu);
                }
            }

            mm_api.document.getPrintFormList({id: doc.id}, onLoadPFLSuccess, onLoadPFLError);

            return false;
        };

        doc.emailForms = function (event) {
            event.preventDefault();
            var menu = CreateContextMenu(event);
            var email = '';

            function pickItem(event) {
                var fname = event.target.fname;

                menu.innerHTML = '';
                menu.morphToDialog();
                var elem = document.createElement('div');
                elem.innerHTML = 'Адрес электронной почты:';
                menu.appendChild(elem);
                var imail = document.createElement('input');
                imail.type = 'tel';
                imail.value = email;
                imail.style.width = '200px';
                menu.appendChild(imail);
                elem = document.createElement('div');
                elem.innerHTML = 'Комментарий:';
                menu.appendChild(elem);
                var mailtext = document.createElement('textarea');
                menu.appendChild(mailtext);
                menu.appendChild(document.createElement('br'));


                elem = document.createElement('br');
                menu.appendChild(elem);
                var bcancel = document.createElement('button');
                bcancel.innerHTML = 'Отменить';
                bcancel.onclick = function () {
                    menu.parentNode.removeChild(menu);
                };
                menu.appendChild(bcancel);
                var bsend = document.createElement('button');
                bsend.innerHTML = 'Отправить';
                menu.appendChild(bsend);
                bsend.onclick = function () {
                    mm_api.document.sendEmail({
                        id: doc.id,
                        email: imail.value,
                        name: fname,
                        text: mailtext.value
                    }, onLoadPFLSuccess, onLoadPFLError);
                    menu.innerHTML = '<img src="/img/icon_load.gif" alt="отправка">Отправка email...';
                };

            }

            function onLoadPFLError(response, data) {
                jAlert(response.errortype + ': ' + response.errormessage, 'Отправка email', null, 'icon_err');
                menu.parentNode.removeChild(menu);
            }

            function onLoadPFLSuccess(response) {
                if (response.action == 'getprintformlist') {
                    menu.innerHTML = '';
                    var printforms = response.content.printforms;
                    for (var i = 0; i < printforms.length; i++) {
                        var elem = document.createElement('div');
                        if (printforms[i].mime) {
                            var mime = printforms[i].mime.replace('/', '-');
                            elem.style.backgroundImage = "url('/img/mime/22/" + mime + ".png')";
                        }
                        elem.innerHTML = printforms[i].desc;
                        elem.fname = printforms[i].name;
                        elem.onclick = pickItem;
                        menu.appendChild(elem);
                    }
                }
                else if (response.action == 'sendemail') {
                    jAlert('Сообщение успешно отправлено!', "Выполнено");
                    menu.parentNode.removeChild(menu);
                }
                else {
                    jAlert("Обработка полученного сообщения не реализована", "Отправка email", null, 'icon_err');
                    menu.parentNode.removeChild(menu);
                }
            }

            mm_api.document.getPrintFormList({id: doc.id}, onLoadPFLSuccess, onLoadPFLError);

            return false;
        };

        doc.subordinateDialog = function (event) {
            event.preventDefault();
            var p_doc_tmp = 0;

            function onEnterData(result) {
                if (result !== null) {
                    mm_api.document.subordinate({id: doc.id, p_doc: result}, onLoadSuccess, onLoadError);
                    p_doc_tmp = result;
                }
            }

            function onLoadError(response, data) {
                jAlert(response.errortype + ': ' + response.errormessage, 'Подчинение документа', null, 'icon_err');
            }

            function onLoadSuccess(response) {
                doc.header.p_doc = p_doc_tmp;
                jAlert('Документ ' + doc.id + ' успешно подчинён документу ' + p_doc_tmp, 'Подчинение документа');
            }

            jPrompt("Укажите <b>системный</b> номер документа,<br> к которому привязать <br>текущий документ:",
                doc.header.p_doc, "Подчинение документа", onEnterData);
            return false;
        }

        doc.morphToMenu = function (event) {
            event.preventDefault();
            var menu = CreateContextMenu(event);

            function onLoadMMError(response, data) {
                menu.destroy();
                jAlert(response.errortype + ': ' + response.errormessage, 'Морфинг', null, 'icon_err');
            }

            function onLoadMMSuccess(response) {
                menu.innerHTML = ''
                var morphlist = response.content.morphlist;
                var i, c = 0;

                for (i in morphlist) {
                    var elem = document.createElement('div');
                    var docfname = morphlist[i].document.replace('/', '-');
                    elem.style.backgroundImage = "url('/img/doc/" + docfname + ".png')";
                    elem.innerHTML = morphlist[i].viewname;
                    elem.fname = morphlist[i].name;
                    elem.onclick = pickItem;
                    menu.appendChild(elem);
                    c++;
                }
                if (c == 0) {
                    menu.destroy();
                    jAlert('На основе этого документа нельзя создать ни один другой документ.', 'Морфинг', null, 'icon_err');
                }
            }

            function onMorphSuccess(response) {
                var newdoc_id = response.content.newdoc_id;
                history.pushState({doc_id: newdoc_id}, null, '/test_doc.php?doc_id=' + newdoc_id);
                //history.replaceState({doc_id: doc_id}, null, window.href);
                doc.init(newdoc_id);
            }

            function pickItem(event) {
                var fname = event.target.fname;
                menu.destroy();
                var data = {id: doc.id, target: fname};
                mm_api.document.morph(data, onMorphSuccess, onLoadMMError);
                //window.location = "/api.php?object=document&action=getprintform&data="+encodeURIComponent(JSON.stringify(data));
            }

            mm_api.document.getMorphList({id: doc.id}, onLoadMMSuccess, onLoadMMError);
            return false;
        }

        doc.refillNomenclatureForm = function (event) {
            //var menu = CreateContextMenu(event);
            //menu.morphToDialog();
            var headStr = "Перезапись номенклатурной таблицы";
            var selected_row = null;

            function selectRow(event) {
                var obj = event.target;
                while (obj != 'undefined' && obj != 'null') {
                    if (obj.tagName == 'TR') {
                        if (!obj.marked) {
                            obj.style.backgroundColor = '#8f8';
                            obj.marked = 1;
                            if (selected_row) {
                                selected_row.style.backgroundColor = '';
                                selected_row.marked = 0;
                            }
                            selected_row = obj;
                            doc_id_refill.value = obj.doc_id;
                        }
                        else {
                            obj.style.backgroundColor = '';
                            obj.marked = 0;
                            selected_row = null;
                            doc_id_refill.value = '';
                        }
                        return;
                    }
                    obj = obj.parentNode;
                }
            }

            var dialogStr = "<table width='100%' class='list'><thead><tr><th colspan='4'>Заполнить из документа</th></tr></thead><tbody id='doc_sel_table_body'>";
            dialogStr = dialogStr + "</tbody></table>" +
                "<table width='100%'><tr><td><label><input type='checkbox' id='p_clear_cb'> Предочистка</label></td><td>Док.id:<input type='text' id='doc_id_refill'></td>" +
                "<td><label><input type='checkbox' id='nsum_cb'> Не суммировать</label></td></tr><tr><td><button id='bcancel'>Отменить</button></td>" +
                "<td></td><td style='text-align:right'><button id='bok'>Выполнить</button></td></table>";

            var menu = createModalLayer(headStr, dialogStr);

            var doc_sel_table_body = document.getElementById('doc_sel_table_body');
            var op_clear_cb = document.getElementById('p_clear_cb');
            var onsum_cb = document.getElementById('nsum_cb');
            var obok = document.getElementById('bok');
            var obcancel = document.getElementById('bcancel');
            var doc_id_refill = document.getElementById('doc_id_refill');

            doc_sel_table_body.onclick = selectRow;
            for (i in doc.data.sub_info) {
                var tr = doc_sel_table_body.insertRow(-1);
                var sub_info = doc.data.sub_info[i];
                var str = "<td>" + sub_info.id + "</td><td>" + sub_info.viewname + "</td><td>" + sub_info.altnum + sub_info.subtype + "</td><td>" + sub_info.date + "</td>";
                tr.innerHTML = str;
                tr.doc_id = sub_info.id;
                tr.style.cursor = 'pointer';
            }

            obcancel.onclick = function () {
                menu.destroy();
            };
            obok.onclick = function () {
                if (!doc_id_refill.value) {
                    return false;
                }
                var data = {
                    id: doc.id,
                    from_doc_id: doc_id_refill.value,
                    preclear: op_clear_cb.checked ? 1 : 0,
                    no_sum: onsum_cb.checked ? 1 : 0
                };
                mm_api.document.refillPosList(data, refillSuccess, refillError);
                menu.innerHTML = '<img src="/img/icon_load.gif" alt="Загрузка">Загрузка...';
            };

            function selectNum(event) {
                var odoc_num_field = document.getElementById('doc_num_field');
                odoc_num_field.value = event.target.doc_id;
            }

            function refillError(response, data) {
                menu.destroy();
                jAlert(response.errortype + ': ' + response.errormessage, headStr, null, 'icon_err');
            }

            function refillSuccess(response, data) {
                try {
                    if (response.response == 'success') {
                        jAlert('Таблица загружена', "Выполнено", {});
                        menu.destroy();
                        poslist.refresh();
                    }
                    else {
                        jAlert("Обработка полученного сообщения не реализована<br>" + response, headStr, {}, 'icon_err');
                        menu.destroy();
                    }
                }
                catch (e) {
                    jAlert("Критическая ошибка!<br>Если ошибка повторится, уведомите администратора о том, при каких обстоятельствах возникла ошибка!" +
                        "<br><br><i>Информация об ошибке</i>:<br>" + e.name + ": " + e.message + "<br>" + response, headStr, {}, 'icon_err');
                    menu.destroy();
                }
            }

            return false;
        }

        window.addEventListener("popstate", function (e) {
            if (e.state != null) {
                doc.init(e.state.doc_id);
            }
            else {
                //history.go(0);
            }
        }, false)

        return doc;
    };

    return doceditor;
});
