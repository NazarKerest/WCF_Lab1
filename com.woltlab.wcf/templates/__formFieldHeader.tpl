	<dt><label for="{@$field->getPrefixedId()}">{@$field->getLabel()}</label></dt>
<dl id="{@$field->getPrefixedId()}Container" {if !$field->getClasses()|empty} class="{implode from=$field->getClasses() item='class' glue=' '}{$class}{/implode}"{/if}{foreach from=$field->getAttributes() key='attributeName' item='attributeValue'} {$attributeName}="{$attributeValue}"{/foreach}{if !$field->checkDependencies()} style="display: none;"{/if}>
	<dd>
