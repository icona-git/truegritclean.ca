<label class="quote-upload" for="$ID">
    <input $AttributesHTML aria-describedby="{$Name}_status<% if $RightTitle %> {$Name}_right_title<% end_if %>"<% if $Message %> aria-invalid="true"<% end_if %>>
    <svg aria-hidden="true" viewBox="0 0 32 32">
        <path d="M16 22V5m0 0-6 6m6-6 6 6M6 20v7h20v-7" />
    </svg>
    <strong><%t App\Forms\PhotosUploadField.DROP 'Drop site photos here' %></strong>
    <span class="quote-upload__button"><%t App\Forms\PhotosUploadField.BUTTON 'Add photos' %></span>
    <small><%t App\Forms\PhotosUploadField.TYPES 'JPG or PNG · Multiple files accepted' %></small>
</label>
