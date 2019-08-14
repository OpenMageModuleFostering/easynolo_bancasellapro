if (typeof BancaSellaForm == 'undefined') {

    var BancaSellaForm =  {
        formId : false,
        merchantId : false,
        stringEnctipt : false,
        radioBancaSella : false,
        cssSelectorInfo : false,
        GestPayExternalClass : false,
        enable : false,
        idStart : false,
        cssSelectorRadioPayment : false,
        redirectSuccessUrl : false,
        redirectAfterIframe : false,
        confirmPage : false,
        authPage : false,
        dialogCC : false,
        lock: false,
        modal3d : false,
        successRedirect : false,
        code:null,
        enableFormToIframe:false,
        isRecurringProfile:false,

        init : function (config){
            this.formId = config.formId;
            this.merchantId = config.merchantId;
            this.stringEnctipt = config.stringEnctipt;
            this.radioBancaSella = config.radioBancaSella;
            this.cssSelectorInfo = config.cssSelectorInfo;
            this.idStart = config.idStart;
            this.cssSelectorRadioPayment = config.cssSelectorRadioPayment;
            this.showHidePaymentDivId = config.showHidePaymentDivId;
            this.confirmPage = config.confirmPage;
            this.authPage = config.authPage;
            this.waitImage = config.waitImage;
            this.GestPayExternalClass = config.GestPayExternalClass;
            this.successRedirect = config.successRedirect;
            this.enableFormToIframe= config.enableFormToIframe;
            this.code= config.code;
            this.isRecurringProfile= config.isRecurringProfile;

            if(!this.enableFormToIframe && this.isRecurringProfile){
                this.removePaymentMethod();
            }else if (this.enableFormToIframe){
                this.start();
                // al click sulla form visibile e con input disabilitati, la funzione li riabilita
                $(this.showHidePaymentDivId).down('.step-title').on('click', this.checkClickPayment.bind(this));
            }
        },
        start : function(){
            if(!this.lock){

                this.showWait();
                this.GestPayExternalClass.CreatePaymentPage( this.merchantId, this.stringEnctipt, this.paymentPageLoad);

                $$(this.cssSelectorRadioPayment).each(
                    function(item){
                        Event.observe(item, 'click', BancaSellaForm.togglePaymentForm);
                    }
                );
                //aggiorniamo lo stato del form al caricamento del pagamento
                payment.addAfterInitFunction('update-get-pay-form',BancaSellaForm.togglePaymentForm);

                //aggiungiamo la validazione del form con i dati della carta nello step di pagamento
                // e la rimozione del form per non far inviare i dati
                payment.addBeforeValidateFunction('before-validate-gest-pay',function(){
                    if(!BancaSellaForm.lock){
                        if(BancaSellaForm.enable){
                            var validator = new Validation($(BancaSellaForm.formId ));
                            if (!validator.validate())
                                return false;
                            var form =$(BancaSellaForm.formId );
                            BancaSellaForm.realForm=form;
                            Form.getElements(form).each(function (input){
                                input.disabled=true;
                            });
                        }
                    }
                    //restituisco true per la validazione
                    return true;
                });

            }
        },

        checkClickPayment : function (){
            if(BancaSellaForm.enable
                && $(BancaSellaForm.showHidePaymentDivId).hasClassName('allow')) {
                Form.getElements($(BancaSellaForm.formId )).each(function (input){
                    if(input.hasAttribute('disabled') && input.readAttribute('disable') != false ){
                        BancaSellaForm.enableInputs();
                        return;
                    }
                });
            }
        },
        toggleStatusForm : function (isEnable){
            if(isEnable){
                this.lock = false;
                this.enable = false;
                this.togglePaymentForm();
                //nascondo il messaggio del redirect dopo la conferma ordine
                $$(this.cssSelectorInfo).first().hide();
            }else{
                this.disableForm();
                //mostro il messaggio del redirect dopo la conferma ordine
                $$(this.cssSelectorInfo).first().show();
                this.lock = true;
            }
        },
        toggleForm: function (){
            if(!this.lock){
                if(this.enable && this.formId){
                    this.disableForm();
                }
                else{
                    this.enableForm();
                }
            }
        },
        enableForm : function (){
            if(!this.lock){
                if(!this.enable ){
                    $(this.formId).show();
                    Form.enable( $(this.formId));
                    this.enable = true;
                }
                return true;
            }
            return false;
        },
        disableForm : function(){
            if(!this.lock){
                if(this.enable ){
                    $(this.formId).hide();
                    Form.disable( $(this.formId));
                    this.enable = false;
                }
                return true;
            }
            return false;
        },
        enableInputs : function (){
            Form.getElements($(BancaSellaForm.formId )).each(function (input){
                input.disabled=false;
            });
        },
        debugInputForm : function (){
            var obj ={
                CC : $F($(this.idStart+'_cc_number')),
                EXPMM : $F($(this.idStart+'_cc_exp_mm')),
                EXPYY : $F($(this.idStart+'_cc_exp_yy')),
                CVV2 : $F($(this.idStart+'_cc_cvv')),
                Name: $F($(this.idStart+'_cc_name')),
                Email: $F($(this.idStart+'_cc_email'))
            };
            console.log(obj);
        },

        togglePaymentForm : function (){
            if($(BancaSellaForm.radioBancaSella).checked){
                BancaSellaForm.enableForm();
            }else{
                BancaSellaForm.disableForm();
            }
            return false;
        },
        paymentPageLoad : function( Result ){
            BancaSellaForm.hideWait();
            if(Result.ErrorCode != 10){
                BancaSellaForm.toggleStatusForm(false);
            }else{
                BancaSellaForm.toggleStatusForm(true);
            }
        },
        sendPaymentIframe : function (){
            BancaSellaForm.showWait();
            BancaSellaForm.GestPayExternalClass.SendPayment ({
                    CC : BancaSellaForm.ccData.cc,
                    EXPMM : BancaSellaForm.ccData.expmm,
                    EXPYY : BancaSellaForm.ccData.expyy,
                    CVV2 : BancaSellaForm.ccData.cvv2,
                    Name: BancaSellaForm.ccData.name,
                    Email: BancaSellaForm.ccData.email
                },
                function ( Result ) {
                    BancaSellaForm.hideWait();
                    BancaSellaForm.analizeResponse.delay(0.8,Result);
                }
            );
            return true;
        },
        analizeResponse : function(Result){

            if (Result.ErrorCode != 0){
                if (Result.ErrorCode == 8006){
                    //3D Transaction
                    var TransKey = Result.TransKey;
                    var VBVRisp = Result.VBVRisp;
                    BancaSellaForm.call3dSecure ( TransKey, VBVRisp );
                }else{

                    var idErrorInput = '';
                    if(Result.ErrorCode == 1119 || Result.ErrorCode == 1120){
                        idErrorInput= BancaSellaForm.idStart+'_cc_number';
                    }else
                    if(Result.ErrorCode == 1124 || Result.ErrorCode == 1126){
                        idErrorInput= BancaSellaForm.idStart+'_cc_exp_mm'
                    } else
                    if(Result.ErrorCode == 1125){
                        idErrorInput= BancaSellaForm.idStart+'_cc_exp_yy'
                    }else
                    if(Result.ErrorCode == 1149){
                        idErrorInput= BancaSellaForm.idStart+'_cc_cvv'
                    }else
                    {
                        //altri errori, uno dei possibili 4707
                        Dialog.alert(Result.ErrorDescription,
                            {
                                className:'magento',
                                width:300,
                                height:90,
                                zIndex:1000,
                                okLabel: Translator.translate('Complete payment on Banca Sella website'),
                                buttonClass: "scalable",
                                id: "alertRedirect",
                                title: Translator.translate('Payment authorization error'),
                                onOk: BancaSellaForm.redirectPaymentPage
                            }
                        );
                        return false;
                    }
                    BancaSellaForm.showModalDialogCC(idErrorInput, Result.ErrorDescription);
                    return false;
                }
            }else{
                //pagamento effettuato con successo oppure l'utente ha annullato il 3dsecure;
                url = BancaSellaForm.successRedirect + '?a='+ BancaSellaForm.merchantId + '&b='+ Result.EncryptedString;
                location.href = url;
                return;
            }
        },
        call3dSecure : function (TransKey, VBVRisp){
            BancaSellaForm.transKey=TransKey;
            var a = this.merchantId;
            var b = VBVRisp;
            var c= BancaSellaForm.confirmPage;
            var definitiveUrl =  BancaSellaForm.authPage+'?a='+a+'&b='+b+'&c='+c ;
            BancaSellaForm.showModal(definitiveUrl);
        },
        redirectPaymentPage : function (){
            setLocation(BancaSellaForm.successRedirect);
            return;
        },
        showModal : function (url)
        {
            this.modal3d = new Window(
                {
                    className:'magento',
                    id:'gestpay_window',
                    title:Translator.translate('3D secure'),
                    url:url,
                    width:400,
                    height:400,
                    zIndex:1000,
                    minimizable: false,
                    maximizable : false,
                    closable:false,
                    destroyOnClose:true,
                    recenterAuto:true
                });
            this.modal3d.setZIndex(1000);
            this.modal3d.showCenter(true);
        },
        //chiamata dall'iframe e non in questa pagina
        sendPares : function (pares){
            this.modal3d.close();
            BancaSellaForm.showWait.delay(0.8);
            this.GestPayExternalClass.SendPayment ({
                    PARes : pares ,
                    TransKey : BancaSellaForm.transKey
                },
                function ( Result ) {
                    BancaSellaForm.hideWait();
                    BancaSellaForm.analizeResponse.delay(0.8,Result);
                }
            );
            return false;
        },
        showModalDialogCC : function( id, message ){
            var form = BancaSellaForm.realForm;

            BancaSellaForm.populateForm(form,id,false);

            BancaSellaForm.checkAndCloseDialogCC();

            if (!BancaSellaForm.dialogCC){

                BancaSellaForm.dialogCC = Dialog.alert('<div id="modal-form"><ul class="form-list">'+form.innerHTML+'</ul></div>',
                    {
                        className:'magento',
                        closeOnEsc:false,
                        width:300,
                        height:400,
                        zIndex:1000,
                        okLabel: Translator.translate('Send Credit Card Data'),
                        buttonClass: "scalable",
                        id: "dialogcc",
                        title: Translator.translate('Please correct the highlighted fields'),
                        onOk: function (){
                            var validator = new Validation($('modal-form'));
                            if (!validator.validate())
                                return false;
                            BancaSellaForm.saveDataCC($('modal-form'));

                            BancaSellaForm.sendPaymentIframe.delay(0.8);
                            return true;
                        }
                    }
                );
            }
            alert(message);
//            BancaSellaForm.openAlert(message);
        },
        openAlert : function (message){
            Dialog.alert(message,
                { className:'magento', width:280, height:100, okLabel: "ok",
                    ok:function() { return true;}
                }
            );
        },
        showWait : function(){
            Dialog.info('<img src="'+BancaSellaForm.waitImage+'" class="v-middle" />'+ Translator.translate('Please wait...'),
                { className:'magento',
                    width:150,
                    height:50,
                    zIndex:1000
                }
            );
        },
        hideWait: function(){
            Dialog.closeInfo();
        },
        saveDataCC :function (form){
            BancaSellaForm.ccData=[];
            BancaSellaForm.ccData.cc = $F(form.getElementsBySelector('#'+BancaSellaForm.idStart+'_cc_number').first());
            BancaSellaForm.ccData.expmm = $F(form.getElementsBySelector('#'+BancaSellaForm.idStart+'_cc_exp_mm').first());
            BancaSellaForm.ccData.expyy = $F(form.getElementsBySelector('#'+BancaSellaForm.idStart+'_cc_exp_yy').first());
            BancaSellaForm.ccData.cvv2 = $F(form.getElementsBySelector('#'+BancaSellaForm.idStart+'_cc_cvv').first());
            BancaSellaForm.ccData.name = $F(form.getElementsBySelector('#'+BancaSellaForm.idStart+'_cc_name').first());
            BancaSellaForm.ccData.email = $F(form.getElementsBySelector('#'+BancaSellaForm.idStart+'_cc_email').first());
            return true;
        },
        populateForm:function(form,id,enable){
            Form.getElements(form).each(function (input){
                input.disabled=enable;
                if(input.id == id){
                    input.addClassName('validation-failed');
                }else {
                    input.removeClassName('validation-failed');
                }
                switch (input.id){
                    case BancaSellaForm.idStart+'_cc_number':
                        input.setAttribute('value',BancaSellaForm.ccData.cc);
                        break;
                    case BancaSellaForm.idStart+'_cc_exp_mm':
                        options = input.childElements();
                        len = options.length;
                        for (var i = 0; i < len; i++) {
                            if(options[i].value == BancaSellaForm.ccData.expmm){
                                options[i].setAttribute('selected',true);
                            }
                        }
                        break;
                    case BancaSellaForm.idStart+'_cc_exp_yy':
                        options = input.childElements();
                        len = options.length;
                        for (var i = 0; i < len; i++) {
                            if(options[i].value == BancaSellaForm.ccData.expyy){
                                options[i].setAttribute('selected',true);
                            }
                        }
                        break;
                    case BancaSellaForm.idStart+'_cc_cvv':
                        input.setAttribute('value',BancaSellaForm.ccData.cvv2);
                        break;
                    case BancaSellaForm.idStart+'_cc_name':
                        input.setAttribute('value',BancaSellaForm.ccData.name);
                        break;
                    case BancaSellaForm.idStart+'_cc_email':
                        input.setAttribute('value',BancaSellaForm.ccData.email);
                        break;
                    default :
                        console.log('non ho trovato '+ input.id);
                }
            });
        },
        checkAndCloseDialogCC : function(){
            if(BancaSellaForm.dialogCC){
                Windows.close('dialogcc');
                BancaSellaForm.dialogCC=false;
            }
        },
        removePaymentMethod : function(){
            if(this.formId){
                $(this.formId).hide();
            }
            var $paymentForm = $('p_method_' + this.code);
            $paymentForm.setAttribute('disabled','disabled');
            $paymentForm.up().up().hide();
        }
    };
}

if (typeof Review != 'undefined') {
    Review.addMethods({
        nextStep: function(transport){
            if (transport && transport.responseText) {
                response = eval('(' + transport.responseText + ')');

                if (!response.success) {
                    var msg = response.error_messages;
                    if (typeof(msg)=='object') {
                        msg = msg.join("\n");
                    }
                    if (msg) {
                        alert(msg);
                    }
                    if (response.update_section) {
                        $('checkout-'+response.update_section.name+'-load').update(response.update_section.html);
                    }
                    if (response.goto_section) {
                        checkout.gotoSection(response.goto_section);
                    }
                }else{
                    this.isSuccess = true;
                    BancaSellaForm.redirectSuccessUrl = this.successUrl;
                    if(!BancaSellaForm.lock && ($(BancaSellaForm.radioBancaSella).checked)){

                        if (response.success) {
                            BancaSellaForm.redirectAfterIframe = this.successUrl;
                        }
                        if (response.redirect) {
                            BancaSellaForm.redirectAfterIframe = response.redirect;
                        }

                        BancaSellaForm.saveDataCC(BancaSellaForm.realForm);
                        //inviamo i dati della carta
                        BancaSellaForm.sendPaymentIframe();
                    }else{
                        if (response.redirect) {
                            location.href = response.redirect;
                            return;
                        }
                        if (response.success) {
                            window.location=this.successUrl;
                        }
                    }
                }
            }
        }
    });
}