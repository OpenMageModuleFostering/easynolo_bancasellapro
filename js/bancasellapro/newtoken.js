if (typeof EasyNoloTokenization == 'undefined') {

    var EasyNoloTokenization =  {

        init : function (config){

            this.formId = config.formId;
            this.merchantId = config.merchantId;
            this.stringEnctipt = config.stringEnctipt;
            this.waitImage = config.waitImage;
            this.GestPayExternalClass = config.GestPayExternalClass;
            this.successRedirect = config.successRedirect;
            this.enableFormToIframe= config.enableFormToIframe;
            this.code= config.code;
            this.disableProfileRedirect = config.disableProfileRedirect;

            if(!this.enableFormToIframe ){
                this.suspendProfilePage();
            }else {
                this.start();
            }
        },
        start : function(){
            this.showWait();
            this.GestPayExternalClass.CreatePaymentPage( this.merchantId, this.stringEnctipt, this.paymentPageLoad);
        },
        paymentPageLoad : function( Result ){
            EasyNoloTokenization.hideWait();
            if(Result.ErrorCode != 10){
                EasyNoloTokenization.suspendProfilePage(Result.ErrorDescription);
            }
        },
        showWait : function(){
            Dialog.info('<img src="'+EasyNoloTokenization.waitImage+'" class="v-middle" />'+ Translator.translate('Please wait...'),
                { className:'magento',
                    width:150,
                    height:50,
                    zIndex:1000
                }
            );
        },
        getFormContent: function(name){
            return $F(EasyNoloTokenization.code+name);
        },
        sendPaymentIframe : function (){

            EasyNoloTokenization.showWait();
            EasyNoloTokenization.GestPayExternalClass.SendPayment ({
                    CC : EasyNoloTokenization.getFormContent('_cc_number'),
                    EXPMM : EasyNoloTokenization.getFormContent('_cc_exp_mm'),
                    EXPYY : EasyNoloTokenization.getFormContent('_cc_exp_yy'),
                    CVV2 : EasyNoloTokenization.getFormContent('_cc_cvv'),
                    Name: EasyNoloTokenization.getFormContent('_cc_name'),
                    Email: EasyNoloTokenization.getFormContent('_cc_email')
                },
                function ( Result ) {
                    EasyNoloTokenization.hideWait();
                    EasyNoloTokenization.analizeResponse.delay(0.8,Result);
                }
            );
            return true;
        },
        hideWait: function(){
            Dialog.closeInfo();
        },

        analizeResponse : function(Result){

            if (Result.ErrorCode != 0){
                if (Result.ErrorCode == 8006){
                    //non gestiamo il 3d secure perché non è possibile effettuare pagamenti ricorrenti
                    EasyNoloTokenization.suspendProfilePage();
                }else{

                    var idErrorInput = '';
                    if(Result.ErrorCode == 1119 || Result.ErrorCode == 1120){
                        idErrorInput= EasyNoloTokenization.code+'_cc_number';
                    }else
                    if(Result.ErrorCode == 1124 || Result.ErrorCode == 1126){
                        idErrorInput= EasyNoloTokenization.code+'_cc_exp_mm'
                    } else
                    if(Result.ErrorCode == 1125){
                        idErrorInput= EasyNoloTokenization.code+'_cc_exp_yy'
                    }else
                    if(Result.ErrorCode == 1149){
                        idErrorInput= EasyNoloTokenization.code+'_cc_cvv'
                    }else
                    {
                        EasyNoloTokenization.suspendProfilePage();
                        return false;
                    }
                    EasyNoloTokenization.showErrorMessageCC(idErrorInput, Result.ErrorDescription);
                    return false;
                }
            }else{
                //pagamento effettuato con successo oppure l'utente ha annullato il 3dsecure;
                url = EasyNoloTokenization.successRedirect + '?a='+ EasyNoloTokenization.merchantId + '&b='+ Result.EncryptedString;
                location.href = url;
                return;
            }
        },

        showErrorMessageCC: function (idError, message){
            EasyNoloTokenization.hideWait();
            alert(message);
        },

        suspendProfilePage : function(description){
            url = EasyNoloTokenization.disableProfileRedirect
            location.href = url;
            return;
        }

    }
}