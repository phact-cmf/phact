{foreach $form->getInitFields() as $name => $field}
    <div class="form-field {$name}">
        {$field->render()}
    </div>
{/foreach}