var products;
var card = [];
var sp = [-1, null];
var email = [false, ""];
//PRODUCTS
function getProducts() {
    return JSON.parse($.ajax({
        type: "GET",
        url: "/processor.php",
        data: {json:JSON.stringify({'path':30})},
        async: false,
    }).responseText);
    
}
function getProductDiv(id, name, cost, count, desc) {
	return'<div class="thumbnail">' +
			'<div class="image"><img alt="" src="/icons/'+id+'.jpg" title=""></div>' +
			'<div class="caption">' +
				'<div class="name">' + name + '</div>' +
				'<div class="description">' + desc + '</div>' +
				'<div class="buy">' +
					'<a class="to_card" href="javascript:addToCard(' + id + ')">' +
					'<div class="cost">' + parseInt(cost) + '<text style="font-size: 11.5px;">.' + ((parseFloat(cost) * 100) % 100) + '</text> руб.</div>' +
					'<div class="to_card_b">Купить</div></a>' +
				'</div>' +
			'</div>' +
		'</div>';
}
function showProducts(products) {
    $("#products").children('.thumbnails').html("");
    $.each( products, function( key, value ) {
        $("#products").children('.thumbnails').append(getProductDiv(value.id, value.shortname, value.price, value.count,value.description));
    });
}
function getProduct(id) {
    var k = -1;
    $.each( products, function( key, value ) {
        if(parseInt(value.id, 10) == id) {
            k = key;
        }
    });
    return k;
}
//PRODUCTS




//BuyBox
function buyBoxDiv(id, count) {
    
    var pid = getProduct(card[id][0]);
	return '<div class="cardproduct">' +
				'<img alt="" src="/icons/'+products[pid].id+'.jpg" title="">' +
				'<a class="name" href="/'+pid+'/">'+products[pid].shortname+'</a>' +
				'<p class="cost">[<text class="count1">'+count+'</text>×'+parseFloat(products[pid].price).toFixed(2)+' ₽]</p><a class="remove" href="javascript:void(0)" onclick="removeFromCard(' + id + ', this);">✖</a>' +
				'<div class="amount"><text class="s">'+(count * products[pid].price).toFixed(2)+'</text> руб.</div>' +
				'<div class="count">' +
					'<a class="countb remove" href="javascript:void(0)" onclick="removeOneCard('+id+',this);">-</a> <input class="inputcount" oninput="checkCount(this, ' + id + ', ' + pid + ');" type="text" value="'+count+'"> <a class="countb add" href="javascript:void(0)" onclick="addOneCard('+id+',this);">+</a>' +
				'</div>' +
			'</div>';                    
}
function getCardSum() {
    var sum = 0.00;
	
    card.forEach(function(item, index, array) {
      sum += products[getProduct(item[0])].price*item[1];
    });
    return sum;
}
function updateCardSum() {
    $("#cardNext").children('.buy').children('.to_card').children('.cost').html(getCardSum().toFixed(2) + " руб.");
    $("#cardsum").html(getCardSum().toFixed(2) + " ₽");
    
}
function showBuyBox() {
    $("#buyw").fadeIn(400);
	activatePayButton();
}
function hideBuyBox() {
	$("#buyw").fadeOut(400);
    $("#CardProducts").delay(400).queue(function () {
        $(this).show();
        $("#CardBoxName").html("Корзина");
        $("#typeOfPay").hide();
        $("#waitingforpay").hide();
    	$("#waitingforpay").children('iframe').attr('src', "");
        $("#cardNext").children('.buy').children('.to_card').removeAttr("href");
		$("#email").removeAttr("disabled");
        $(this).dequeue();
    });
}
function cardbck(e) {
	var e = e || window.event;
	var target = e.target || e.srcElement;
	if(this == target) 
		return;
}
//BuyBox

function checkCount(e, id, pid) {
	var c = $(e).val();
	c = parseInt(c, 10);
	if (isNaN(c)) { c = 1;}
	if(c < 1) c = 1;
	if(c > products[pid].count) c = products[pid].count;
	$(e).val(c);
    card[id][1] = c;
    updateCardSum();
    $.cookie("card", JSON.stringify(card),{ expires: 7, path:'/' });
}


//Card
function loadCard() {
    card = JSON.parse($.cookie("card"));
	if(card != null) {
		cardn = card.slice();
		removed = 0;
		cardn.forEach(function(item, index, array) {
			if(getProduct(item[0])== -1) {
				card.splice(index - removed++, 1);
				alert(index);
				
			} else {
				if(getProduct(item[0]).count < item[1]) {
					card.splice(index - removed++, 1);
				}
					
			}
		});
		cardn=null;
		card.forEach(function(item, index, array) {
			addToCardC(index, item[1])
		});
	} else {
		card = [];
	}
    updateCardSum();
}
function addToCard(id) {
    var found = false;
    card.forEach(function(item, index, array) {
      if(item[0] == id) {
          found = true;
      }
    });
    if(!found) {
        card.push([id, 1]);
        $('#CardProducts').append(buyBoxDiv(card.length-1, 1));
        $.cookie("card", JSON.stringify(card), { expires: 7, path:'/' });
        updateCardSum();
    }
    
    showBuyBox();
    activatePayButton();
}
function removeFromCard(id,elements) {
    $('#CardProducts').html("");
    card.splice(id, 1)
    $.cookie("card", JSON.stringify(card), { expires: 7, path:'/' });
    card.forEach(function(item, index, array) {
        addToCardC(index, item[1])
    });
    updateCardSum();
	activatePayButton();
}

function removeOneCard(id,elements) {
    var elementp = $(elements).parent();
    if(card[id][1] == 1)
        return;
    card[id][1]--;
    elementp.parent().children('.cost').children(".count1").html(card[id][1]);
    elementp.children('.inputcount').val(card[id][1]);
    elementp.parent().children('.amount').children('.s').html((card[id][1] * products[getProduct(card[id][0])].price).toFixed(2));
    $.cookie("card", JSON.stringify(card),{ expires: 7, path:'/' });
    updateCardSum();
}
function addOneCard(id,elements) {
    var elementp = $(elements).parent();
    var pid = getProduct(card[id][0]);
    if(card[id][1] == products[pid].count)
        return;
    card[id][1]++;
    elementp.parent().children('.cost').children(".count1").html(card[id][1]);
    elementp.children('.inputcount').val(card[id][1]);
    elementp.parent().children('.amount').children('.s').html((card[id][1] * products[getProduct(card[id][0])].price).toFixed(2));
    $.cookie("card", JSON.stringify(card), { expires: 7, path:'/' });
    updateCardSum();
}
function addToCardC(index, count) {
    $('#CardProducts').append(buyBoxDiv(index, count));
    activatePayButton();
}
function buyCard() {
    $("#CardProducts").fadeOut(200);
    $("#CardBoxName").html("Выберите способ оплаты");
    $("#typeOfPay").delay(200).fadeIn(200);
    $("#email").attr("disabled", "");
	activatePayButton();
	
    
}
function validateEmail(element) {
    var pattern = /^[a-z0-9_\.-]+@[a-z0-9-]+\.[a-z]{2,6}$/i;
    var mail = $(element);
    if(mail.val() == '' || mail.val().search(pattern) != 0){
        mail.removeClass('ok').addClass('err');
        email[0] = false;
    } else {
        email[0] = true;
        mail.removeClass('err').addClass('ok');
    }
    email[1] = mail.val();
    activatePayButton();
}
function activatePayButton() {
    if(card.length != 0 && email[0]) {
        $("#cardNext").children('.buy').children('.to_card').attr("href", "javascript:buyCard();");
        $("#cardNext").children('.buy').children('.to_card').children('.to_card_b').removeClass("poff");
        $("#cardNext").children('.buy').children('.to_card').removeClass("poff");
    } else {
        $("#cardNext").children('.buy').children('.to_card').removeAttr("href");
        $("#cardNext").children('.buy').children('.to_card').children('.to_card_b').addClass("poff");
        $("#cardNext").children('.buy').children('.to_card').addClass("poff");
    }
}
//CARD

function activatePayButtonS() {
    if(sp[0] != -1) {
        $("#cardNext").children('.buy').children('.to_card').attr("href", "javascript:paySys();");
        $("#cardNext").children('.buy').children('.to_card').children('.to_card_b').removeClass("poff");
        $("#cardNext").children('.buy').children('.to_card').removeClass("poff");
    } else {
        $("#cardNext").children('.buy').children('.to_card').removeAttr("href");
        $("#cardNext").children('.buy').children('.to_card').children('.to_card_b').addClass("poff");
        $("#cardNext").children('.buy').children('.to_card').addClass("poff");
    }
}
function showError(num) {
    $("#err-bck").fadeIn(100);
    $("#err").delay(100).fadeIn(200);
    $('#err').children('p').children('text').html(num);
}
function paySys() {
    var ret = JSON.parse($.ajax({
        type: "GET",
        url: "/processor.php",
        data: {json:JSON.stringify({'path':32,'email':email[1],'paysystem':sp[0],'card':card})},
        async: false,
    }).responseText);
    
    if(parseInt(ret.code) == 11) {
        window.open(ret.url, '_blank');
        openDownload(ret.url_id, ret.url);
        showProducts(getProducts());
        $.cookie("card", null, { expires: 7, path:'/' });
		loadCard();
		
    } else {
        showError(ret.code);
    }
}
function openDownload(id, url) {
    $("#typeOfPay").fadeOut(200);
    $("#CardBoxName").html("Ожидание оплаты");
    $("#waitingforpay").delay(200).fadeIn(200);
    $("#waitingforpay").children('iframe').attr('src', "/download.php?id="+id);
    $("#cardNext").children('.buy').children('.to_card').attr("href", url);
    $("#cardNext").children('.buy').children('.to_card').attr('target', '_blank');
}
function payss(id, element) {
    if(sp[0] != -1) { 
        $(sp[1]).removeClass("selected");
    }
    $(element).addClass("selected");
    sp = [id, element];
	activatePayButtonS();
}
/*
function getCheckoutTr(id) {
    var pid = getProduct(card[id][0]);
    return '<tr>' +
        '<td>'+products[pid].shortname+'</td>' +
    '<td>'+card[id][1]+'×'+parseFloat(products[pid].price).toFixed(2)+'₽</td>' +
    '<td>='+ (card[id][1] * products[pid].price).toFixed(2)+ '₽</td>' +
    '</tr>';
}
card.forEach(function(item, index, array) {
    $("#enter_email").children('.cardlistche').children('table').append(getCheckoutTr(index));
});
*/
$( document ).ready(function() {
    products = getProducts();
    showProducts(products);
    loadCard();
//    showBuyBox();
//    buyCard();
    //alert(products);
    //hideBuyBox();
	//showError(1);
    
});