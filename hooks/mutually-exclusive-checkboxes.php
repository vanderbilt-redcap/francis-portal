<script>
	$(function(){
		var mutuallyExclusiveCheckboxGroups = [
			[8, 16], // liver disease
			[9, 12] // diabetes
		]

		var checkboxes = $('#conditions-tr input[type=checkbox]')
		checkboxes.change(function(){
			var clickedCheckbox = $(this)

			if(clickedCheckbox.prop('ignore-next-click') == true){
				clickedCheckbox.prop('ignore-next-click', false)
				return
			}

			var clickedCode = parseInt(clickedCheckbox.attr('code'))
			mutuallyExclusiveCheckboxGroups.forEach(function(group){
				if(group.indexOf(clickedCode) != -1){
					// The clicked code is in this group!
					group.forEach(function(code){
						if(code != clickedCode){
							var checkbox = checkboxes.filter('[code=' + code + ']')
							if(checkbox.prop('checked') == true){
								checkbox.prop('ignore-next-click', true)
								checkbox.click()
							}
						}
					})
				}
			})
		})
	})
</script>
