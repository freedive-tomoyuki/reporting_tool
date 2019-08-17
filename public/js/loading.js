$(function() {
  var h = $(window).height(); //画面の高さを取得
 
  $('#contents').css('display','none');//コンテンツを非表示に
  $('#loader-bg ,#loader').height(h).css('display','block');//ローディング画像を表示
});
 
$(window).load(function () { //読み込み完了したら実行する
  $('#loader-bg').delay(900).fadeOut(800);//ローディングを隠す
  $('#loader').delay(600).fadeOut(300);
  $('#contens').css('display', 'block');//コンテンツを表示する
});
 
$(function(){
  setTimeout('stopload()',10000);　//いつまでもローディング状態にならないように10秒で強制表示させる

});
 
function stopload(){ //強制表示の関数
  $('#contens').css('display','block');
  $('#loader-bg').delay(900).fadeOut(800);
  $('#loader').delay(600).fadeOut(300);
}
