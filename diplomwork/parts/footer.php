</main>

<div id="call-back-wrap">
	<div id="call-back-content">
		<span id="call-back-close"><i class="fa-solid fa-xmark"></i></span>
		<form class="jsCallBack">
			<div class="jsResult"></div>
			<label>
				<span>Ваше имя: <span class="text-red">*</span></span><br>
				<input type="text" name="name" maxlength="255">
			</label>
			
			<label>
				<span>Ваш телефон: <span class="text-red">*</span></span><br>
				<input type="text" name="phone" maxlength="255">
			</label>
			
			<label>
				<span>Примечание:</span><br>
				<textarea name="note" rows="4"></textarea>
			</label>
			
			<label class="agree-text">
				<input type="checkbox" name="agreement"> <span>Даю согласие на хранение и обработку моих персональных данных. <span class="text-red">*</span></span>
			</label>
			
			<div class="centered"><button type="submit">Отправить</button></div>
		</form>
	</div>
</div>
<div id="call-back-backdrop" class="hidden"></div>
<div class="jump-to-top-wrap hidden">
	<div class="jump-to-top-inner container">
		<a href="#top" class="jumpToTop">
			<i class="fa fa-chevron-up"></i>
		</a>
	</div>
</div>
</body>
</html>