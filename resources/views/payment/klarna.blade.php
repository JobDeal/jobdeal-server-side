<html>
<body>

<div style="width: 100%" id="klarna_container"></div>

<input type="button" value="Buy" onclick="buy()">
<script src="https://x.klarnacdn.net/kp/lib/v1/api.js" async></script>
  <script>

  //init klarna
  window.klarnaAsyncCallback = function () {
      console.log("{!! $token !!}");
      Klarna.Payments.init({
        client_token: "{!! $token !!}"
      });

      //load klarna
          Klarna.Payments.load({
              container: '#klarna_container',
              payment_method_category: 'pay_later'
          }, function (res) {
              console.log("Load OK");
              console.log(res);
          });
  };



//on buy click - run klarna authorize
  function buy() {
      Klarna.Payments.authorize({
      payment_method_category: "pay_later"
    }, {}, function(res) {
          console.log(res);
          window.location.href = "http://jobdeal.justraspberry.com/test/payment/finish/" + res.authorization_token;
      });
}
</script>

</body>
</html>
