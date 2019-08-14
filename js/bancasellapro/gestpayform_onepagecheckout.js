if (typeof(BancaSellaForm) == 'undefined') {

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
        initalized : false,
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
                return;
            }

            document.observe("payment-method:switched", function(event) {
                if(event.memo.method_code == "gestpaypro"){
                    if(!BancaSellaForm.initalized){
                        BancaSellaForm.start();
                    }
                    BancaSellaForm.toggleStatusForm(true);
                }else{
                    BancaSellaForm.disableForm();
                }
            });

        },
        start : function(){
            if(!this.lock){
                this.showWait();
                BancaSellaForm.initalized = true;
                BancaSellaForm.createPagePaymentToOrder(false);

                $$(this.cssSelectorRadioPayment).each(
                    function(item){
                        Event.observe(item, 'click', BancaSellaForm.togglePaymentForm);
                    }
                );
                //aggiorniamo lo stato del form al caricamento del pagamento
                payment.addAfterInitFunction('update-get-pay-form',BancaSellaForm.togglePaymentForm);

                //aggiungiamo i dati dell form ad una variabile interna per l'invio dei dati
                payment.addBeforeValidateFunction('before-validate-gest-pay',function(){
                    if(!BancaSellaForm.lock){
                        if(BancaSellaForm.enable){
                            var form =$(BancaSellaForm.formId );
                            BancaSellaForm.realForm=form;
                            return;
                        }
                    }
                    BancaSellaForm.realForm = null;
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
//                if(!this.enable ){
                $(this.formId).show();
                Form.enable( $(this.formId));
                this.enable = true;
//                }
                return true;
            }
            return false;
        },
        disableForm : function(){
//            if(!this.lock){
            if(this.enable ){
                $(this.formId).hide();
                Form.disable( $(this.formId));
                this.enable = false;
//                }
                return true;
            }
            return false;
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
            if(Result.ErrorCode != 10){
                //l'iframe non è stato creato
                if(!this.enableFormToIframe && this.isRecurringProfile){
                    //se il pagamento era per un profilo ricorrente allora rimuovo la form
                    BancaSellaForm.removePaymentMethod();
                    return;
                }else{
                    BancaSellaForm.toggleStatusForm(false);
                }
            }else{
                BancaSellaForm.toggleStatusForm(true);
            }
            BancaSellaForm.hideWait();
            BancaSellaForm.unlockPlaceOrder();
        },
        paymentPageLoadToOrder : function( Result ){
            if(Result.ErrorCode != 10){
                if(!BancaSellaForm.enableFormToIframe && BancaSellaForm.isRecurringProfile){
                    //se il pagamento era per un profilo ricorrente allora rimuovo la form
                    BancaSellaForm.removePaymentMethod();
                    return;
                }else{
                    BancaSellaForm.toggleStatusForm(false);
                }
            }else{
                BancaSellaForm.toggleStatusForm(true);
                IWD.OPC.saveOrder();
                IWD.OPC.Plugin.dispatch('savePaymentAfter');
            }
            BancaSellaForm.hideWait();
        },
        paymentPageLoadToSaveOrder : function( Result ){

            BancaSellaForm.paymentPageLoad(Result);
            IWD.OPC.saveOrder();

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
                setLocation(BancaSellaForm.successRedirect + '?a='+ BancaSellaForm.merchantId + '&b='+ Result.EncryptedString);
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
            if(BancaSellaForm.isRecurringProfile){
                //il pagamento ricorrente non puo essere effettuato su bancasella
                setLocation(BancaSellaForm.redirectAfterIframe);
            }else{
                setLocation(BancaSellaForm.successRedirect);
            }
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
        },
        showWait : function(){
            IWD.OPC.Checkout.showLoader();
            IWD.OPC.Checkout.lockPlaceOrder();
        },
        hideWait: function(){
            IWD.OPC.Checkout.hideLoader();
            IWD.OPC.Checkout.unlockPlaceOrder();
        },
        lockPlaceOrder: function(){
            IWD.OPC.Checkout.lockPlaceOrder();
        },
        unlockPlaceOrder: function(){
            IWD.OPC.Checkout.unlockPlaceOrder();
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
            $(this.formId).hide();
            var $paymentForm = $('p_method_' + this.code);
            $paymentForm.setAttribute('disabled','disabled');
            $paymentForm.up('dt').hide();
        },
        createPagePaymentToOrder: function (callSendOrder){
            IWD.OPC.Checkout.showLoader();
            IWD.OPC.Checkout.lockPlaceOrder();
            if(callSendOrder){
                BancaSellaForm.GestPayExternalClass.CreatePaymentPage( BancaSellaForm.merchantId, BancaSellaForm.stringEnctipt, BancaSellaForm.paymentPageLoadToOrder);
            }
            else{
                BancaSellaForm.GestPayExternalClass.CreatePaymentPage( BancaSellaForm.merchantId, BancaSellaForm.stringEnctipt, BancaSellaForm.paymentPageLoad);
            }
        }
    };
}

// define jquery
if(typeof($j_opc) == 'undefined' || $j_opc == undefined || !$j_opc){
    $j_opc = false;

    if(typeof($ji) != 'undefined' && $ji != undefined && $ji)
        $j_opc = $ji; // from iwd_all 2.x
    else{
        if(typeof($j) != 'undefined' && $j != undefined && $j)
            $j_opc = $j; // from default magento 1.9
        else{
            if(typeof(jQuery) != 'undefined' && jQuery != undefined && jQuery)
                $j_opc = jQuery;
        }
    }
}

if(typeof IWD.OPC != 'undefined'){

    IWD.OPC.prepareOrderResponse = function (response){
        IWD.OPC.Checkout.xhr = null;
        if (typeof(response.error) != "undefined" && response.error!=false){
            IWD.OPC.Checkout.hideLoader();
            IWD.OPC.Checkout.unlockPlaceOrder();
            IWD.OPC.saveOrderStatus = false;
            $j_opc('.opc-message-container').html(response.error);
            $j_opc('.opc-message-wrapper').show();
            IWD.OPC.Plugin.dispatch('error');
            return;
        }

        if (typeof(response.error_messages) != "undefined" && response.error_messages!=false){
            IWD.OPC.Checkout.hideLoader();
            IWD.OPC.Checkout.unlockPlaceOrder();

            IWD.OPC.saveOrderStatus = false;
            $j_opc('.opc-message-container').html(response.error_messages);
            $j_opc('.opc-message-wrapper').show();
            IWD.OPC.Plugin.dispatch('error');
            return;
        }

        if (typeof(response.update_section) != "undefined"){
            IWD.OPC.Checkout.hideLoader();
            IWD.OPC.Checkout.unlockPlaceOrder();
            //create catch for default logic  - for not spam errors to console
            try{
                $j_opc('#checkout-' + response.update_section.name + '-load').html(response.update_section.html);
            }catch(e){

            }
            IWD.OPC.prepareExtendPaymentForm();
            $j_opc('#payflow-advanced-iframe').show();
            $j_opc('#payflow-link-iframe').show();
            $j_opc('#hss-iframe').show();
        }

        if(!BancaSellaForm.lock && ($(BancaSellaForm.radioBancaSella).checked)){
            IWD.OPC.prepareExtendPaymentForm();
            $j_opc('.opc-col-right').find('h3').first().text(Translator.translate('Please wait...'));
            $j_opc('#opc-review-block').html('<div><p>'+Translator.translate('You are waiting the completion of payment')+'</p></div>');

            if (response.redirect) {
                BancaSellaForm.redirectAfterIframe = response.redirect;
            }

            BancaSellaForm.saveDataCC(BancaSellaForm.realForm);
            //inviamo i dati della carta
            BancaSellaForm.sendPaymentIframe();
        }else{
            if (typeof(response.redirect) !="undefined"){
                if (response.redirect!==false){
                    setLocation(response.redirect);
                    return;
                }
            }
        }
        IWD.OPC.Checkout.hideLoader();
        IWD.OPC.Checkout.unlockPlaceOrder();

        IWD.OPC.Plugin.dispatch('responseSaveOrder', response);
    };
    IWD.OPC.preparePaymentResponse = function(response){

        IWD.OPC.Checkout.xhr = null;

        IWD.OPC.agreements = $j_opc('#checkout-agreements').serializeArray();

        IWD.OPC.getSubscribe();

        if (typeof(response.review)!= "undefined"){
            IWD.OPC.Decorator.updateGrandTotal(response);

            $j_opc('#opc-review-block').html(response.review);
            IWD.OPC.Checkout.removePrice();

            // need to recheck subscribe and agreenet checkboxes
            IWD.OPC.recheckItems();
        }

        IWD.OPC.Checkout.hideLoader();
        IWD.OPC.Checkout.unlockPlaceOrder();

        if (typeof(response.error) != "undefined"){

            IWD.OPC.Plugin.dispatch('error');

            $j_opc('.opc-message-container').html(response.error);
            $j_opc('.opc-message-wrapper').show();
            IWD.OPC.Checkout.hideLoader();
            IWD.OPC.Checkout.unlockPlaceOrder();
            IWD.OPC.saveOrderStatus = false;

            return;
        }

        //SOME PAYMENT METHOD REDIRECT CUSTOMER TO PAYMENT GATEWAY
        if (typeof(response.redirect) != "undefined" && IWD.OPC.saveOrderStatus===true){
            IWD.OPC.Checkout.xhr = null;
            IWD.OPC.Plugin.dispatch('redirectPayment', response.redirect);
            if (IWD.OPC.Checkout.xhr==null){
                setLocation(response.redirect);
            }
            else
            {
                IWD.OPC.Checkout.hideLoader();
                IWD.OPC.Checkout.unlockPlaceOrder();
            }

            return;
        }

        if(typeof(response.encrypt_string) != "undefined" ){

            if (typeof IWD.OPC.Checkout.showLoader != "undefined"){
                IWD.OPC.Checkout.showLoader();
            }
            //aggiorno la stringa
            BancaSellaForm.stringEnctipt = response.encrypt_string;

            BancaSellaForm.createPagePaymentToOrder(IWD.OPC.saveOrderStatus===true);

        }else if (IWD.OPC.saveOrderStatus===true){
            IWD.OPC.saveOrder();
        }else{
            if (typeof IWD.OPC.Checkout.hideLoader != "undefined"){
                IWD.OPC.Checkout.hideLoader();
            }
            IWD.OPC.Checkout.unlockPlaceOrder();
        }
        IWD.OPC.Plugin.dispatch('savePaymentAfter');
    }
}

$j_opc.ajaxPrefilter(function( options ) {
    if(decodeURIComponent(options.data).indexOf('payment[method]=gestpaypro') != -1 ){
        //salvo i dati della carta nel oggetto
        BancaSellaForm.saveDataCC(BancaSellaForm.realForm);

        //rimuovo tutti i dati della carta dalla richiesta
        var splitItems= options.data.split('&');
        for(var i=0; i < splitItems.length; ++i){
            if(decodeURIComponent(splitItems[i]).indexOf('bancasella_iframe') == 0 ){
                splitItems.splice(i--,1);
            }
        }
        options.data = splitItems.join('&');
    }
});