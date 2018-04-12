<!-- This code was adapted from the Inline Descriptive Pop-ups external module. -->

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tippy.js/0.3.0/tippy.css" integrity="sha256-s2OFSYvBLhc6NZMWLBpEmjCS9bI27OoN1ckY1z7Z/3w=" crossorigin="anonymous" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/tippy.js/0.3.0/tippy.js" integrity="sha256-eW1TBvNkruqAr5vlt8AwpDDHmoCSq8yfxIpveZ+LO9o=" crossorigin="anonymous"></script>

<style>
	.tippy-popper{
		outline: 0;
	}

	.tippy-popper img{
		max-width: 100%;
	}

	.tippy-tooltip.light-theme{
		color: black;
		font-size: 14px;
		max-width: 500px;
		text-align: left;
	}

	a[popup]{
		cursor: pointer;
	}
</style>
<?php

$terms = [
	'Myocardial infarction' => 'Often referred to as a heart attack; results from decreased blood flow to part of the heart causing damage to the heart muscle. This may be serious or life threatening.',
	'Congestive heart failure' => 'The heart is not able to pump blood properly, which can cause weakness and tiredness, fluid retention, and fluid build-up in the lungs, which can cause shortness of breath.',
	'Peripheral vascular disease' => 'Blood vessel disorder including enlargement of the aorta that can cause tissue death',
	'Dementia' => 'Loss of mental capacity',
	'Chronic pulmonary disease' => 'Often referred to as chronic obstructive lung disease (COPD) or emphysema and causes difficulty breathing due to lung damage',
	'Connective tissue disease' => 'A condition that causes muscles, cartilage and joints not to work properly; includes diseases like lupus, systematic sclerosis, Raynauds phenomenon, Sjogrens Disease, and polymyositis, among others.',
	'Peptic ulcer disease' => 'Painful sores or ulcers in lining of stomach or first part of small intestine',
	'Mild liver disease' => 'Yellowing of the skin (jaundice) due to the accumulation of bilirubin in the blood, itching associated with liver disease, and or easy bruising',
	'Diabetes without end-organ damage' => 'You are taking medication for diabetes but doctors do not think it has affected eyes, nerves, or kidneys or any other organs in your body.',
	'Hemiplegia' => 'Paralysis of or cannot move one or both sides of body due to nerve or brain injury (for example, from stroke or a brain bleed)',
	'Moderate or several renal disease' => 'Your kidneys are not working well and you are building up fluid in your body and wastes that you cannot get rid of by urinating. You may be on dialysis or have discussed the possibility of needing dialysis.',
	'Diabetes with end-organ damage' => 'Diabetes has gotten bad enough that it has caused damage to body organs like the eyes, nerves, and or kidneys. It is called “end-organ damage” because it can affect nearly every organ system.',
	'Tumor without metastases' => 'Tumor or cancer of a specific organ (for example, lung or stomach or other site) that has not spread to other parts of the body. *Do not check if it has been more than 5 years since cancer and you have been told that it is in remission',
	'Leukemia' => 'Cancer of the blood',
	'Lymphoma' => 'Cancer of the lymph nodes',
	'Moderate or sever liver disease' => 'Liver is badly damaged or not working and may be failing. You have been told you may need a liver transplant in the future if it gets worse.',
	'Metastatic solid tumor' => 'Tumor or cancer of a specific organ (for example, lung or stomach or other site) that has spread to other parts of the body',
	'AIDS' => 'A disease caused by the HIV virus that results in severe loss of body’s ability to fight infection and cancers. ',
];

$number = 1;
foreach($terms as $linkText => $text) {
	if(!empty($linkText) && !empty($text)) {
		?>
		<div id="popup-content-<?=$number?>" style="display: none"><?=$text?></div>
		<script>
			$(function(){
				var walker = document.createTreeWalker($('#form')[0])

				var node
				while(node = walker.nextNode()){
					if(node.nodeType == 3 && node.parentElement.nodeName != 'SCRIPT' && node.textContent.trim() != ''){
						// We force the font size to match the original text to get around the REDCap behavior where link font size changes on hover (on surveys).
						var fontSize = $(node.parentNode).css('font-size')

						var newContent = node.textContent.replace(/<?=preg_quote($linkText)?>/g, "<a popup='<?=$number?>' style='font-size: " + fontSize + "'>" + <?=json_encode($linkText)?> + "</a>");
						if(newContent != node.textContent){
							$(node).replaceWith($('<span>' + newContent + '<span>'))
						}
					}
				}
			})
		</script>
		<?php
	}

	$number++;
}

?>
<script>
	$(function(){
		$('a[popup]').each(function() {
			new Tippy(this, {
				html: 'popup-content-' + $(this).attr('popup'),
				trigger: 'mouseenter',
//					trigger: 'click',
				theme: 'light',
				arrow: true,
				interactive: true
			})
		})
	})
</script>