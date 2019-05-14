<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

/**
 * Renders an inline diff view using definition list markup
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class DiffRenderer extends \Diff_Renderer_Html_Array
{

	/**
	 * Render the diff and return the generated markup
	 *
	 * @return string The generated markup
	 */
	public function render()
	{
		$changes = parent::render();

		if (empty($changes))
		{
			return '';
		}

		$html = "\n" . '<div class="change">';

		// Add the field name
		if (isset($this->options['field']))
		{
			$html .= "\n<h2>" . $this->options['field'] . '</h2>';
		}

		$html .= "\n<dl>";

		foreach ($changes as $i=>$blocks)
		{
			if ($i > 0)
			{
				$html .= '<dt class="skipped">…</dt>';
			}

			foreach ($blocks as $change)
			{
				// Show equal changes on both sides of the diff
				if ($change['tag'] == 'equal')
				{
					foreach ($change['base']['lines'] as $line)
					{
						$html .= "\n  " . '<dt class="' . $change['tag'] . ' left">' . ($line ? $this->specialchars($line) : '&nbsp;') . '</dt>';
					}

				}

				// Show added lines only on the right side
				elseif ($change['tag'] == 'insert')
				{
					foreach ($change['changed']['lines'] as $line)
					{
						$html .= "\n " . '<dt class="' . $change['tag'] . ' right"><ins>' . ($line ? $this->specialchars($line) : '&nbsp;') . '</ins></dt>';
					}
				}

				// Show deleted lines only on the left side
				elseif ($change['tag'] == 'delete')
				{
					foreach ($change['base']['lines'] as $line)
					{
						$html .= "\n  " . '<dt class="' . $change['tag'] . ' left"><del>' . ($line ? $this->specialchars($line) : '&nbsp;') . '</del></dt>';
					}
				}

				// Show modified lines on both sides
				elseif ($change['tag'] == 'replace')
				{
					foreach ($change['base']['lines'] as $line)
					{
						$html .= "\n  " . '<dt class="' . $change['tag'] . ' left"><span>' . ($line ? $this->specialchars($line) : '&nbsp;') . '</span></dt>';
					}

					foreach ($change['changed']['lines'] as $line)
					{
						$html .= "\n  " . '<dd class="' . $change['tag'] . ' right"><span>' . ($line ? $this->specialchars($line) : '&nbsp;') . '</span></dd>';
					}
				}
			}
		}

		$html .= "\n</dl>\n</div>\n";

		return $html;
	}

	/**
	 * Converts special characters to HTML entities preserving <ins> and <del> tags
	 *
	 * @param string $line
	 *
	 * @return string
	 */
	private function specialchars($line)
	{
		if ($this->options['decodeEntities'])
		{
			$line = StringUtil::specialchars($line);
			$line = str_replace(array('&lt;ins&gt;', '&lt;/ins&gt;', '&lt;del&gt;', '&lt;/del&gt;'), array('<ins>', '</ins>', '<del>', '</del>'), $line);
		}

		return $line;
	}
}
