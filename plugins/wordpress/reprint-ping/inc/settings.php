<form method="post" action="<?php echo $link;?>?page=settings-<?php echo urlencode(REPRINT_PLUGIN_FILE);?>">
	<table class="form-table">
		<tbody>
			<tr valign="top">
				<th scope="row">
					<label for="reprint-url">Ping URL</label>
				</th>
				<td>
					<input name="reprint-url" type="text" id="reprint-url" value="<?php echo htmlspecialchars($settings['url'], \ENT_QUOTES, 'UTF-8');?>" class="regular-text code">
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">
					<label for="reprint-secret">Ping Secret</label>
				</th>
				<td>
					<input name="reprint-secret" type="text" id="reprint-secret" value="<?php echo htmlspecialchars($settings['secret'], \ENT_QUOTES, 'UTF-8');?>" class="regular-text code">
				</td>
			</tr>
		</tbody>

		<p class="submit">
			<?php wp_nonce_field('reprint-settings'); ?>
			<input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes" />
		</p>
	</table>
</form>
