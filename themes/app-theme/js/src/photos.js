// Quote form photos (App\Forms\PhotosUploadField, templates/App/Forms/PhotosUploadField*.ss).
// Shows each chosen photo under the drop zone (the Figma .upload-previews) and checks the limits the server checks
// again on submit: number of photos, size of each, total size and file type. A problem is shown under the drop zone
// and set as the input's validity message, so the browser will not send the form until it is fixed. The wording comes
// from the input's data-msg-* attributes (PHP, App\Forms\PhotosUploadField::getAttributes()).

function initPhotoField(input) {
  var holder = input.closest('.field');
  if (!holder) {
    return;
  }
  var previews = holder.querySelector('.upload-previews');
  var status = holder.querySelector('.quote-upload__status');
  var maxFiles = parseInt(input.dataset.maxFiles, 10) || 10;
  var maxSize = parseInt(input.dataset.maxSize, 10) || 0;
  var maxTotal = parseInt(input.dataset.maxTotal, 10) || 0;
  var allowed = (input.getAttribute('accept') || '')
    .split(',')
    .map(function (s) { return s.trim().replace(/^\./, '').toLowerCase(); })
    .filter(function (s) { return s && s.indexOf('/') === -1; });
  var urls = [];

  function text(key, values) {
    var out = input.dataset[key] || '';
    Object.keys(values || {}).forEach(function (k) {
      out = out.split('{' + k + '}').join(values[k]);
    });
    return out;
  }

  function showError(message) {
    input.setCustomValidity(message);
    if (status) {
      status.textContent = message;
      status.hidden = !message;
    }
    holder.classList.toggle('field--error', !!message);
  }

  function preview(file) {
    var figure = document.createElement('figure');
    figure.className = 'upload-preview';
    var placeholder = document.createElement('span');
    placeholder.className = 'upload-preview__file';
    placeholder.setAttribute('aria-hidden', 'true');
    placeholder.textContent = (file.name.split('.').pop() || '').toUpperCase();
    if (/^image\//.test(file.type)) {
      var url = URL.createObjectURL(file);
      urls.push(url);
      var img = document.createElement('img');
      img.alt = '';
      img.src = url;
      // A photo the browser cannot draw shows its file type instead of the picture.
      img.addEventListener('error', function () { img.replaceWith(placeholder); });
      figure.appendChild(img);
    } else {
      figure.appendChild(placeholder);
    }
    var caption = document.createElement('figcaption');
    caption.textContent = file.name;
    figure.appendChild(caption);
    return figure;
  }

  function update() {
    urls.forEach(function (url) { URL.revokeObjectURL(url); });
    urls = [];
    var files = Array.prototype.slice.call(input.files || []);
    var total = 0;
    var error = '';
    if (files.length > maxFiles) {
      error = text('msgCount');
    }
    files.forEach(function (file) {
      total += file.size;
      var ext = (file.name.split('.').pop() || '').toLowerCase();
      if (!error && allowed.length && allowed.indexOf(ext) === -1) {
        error = text('msgType', { name: file.name });
      }
      if (!error && maxSize && file.size > maxSize) {
        error = text('msgSize', { name: file.name });
      }
    });
    if (!error && maxTotal && total > maxTotal) {
      error = text('msgTotal');
    }
    showError(error);

    if (!previews) {
      return;
    }
    previews.textContent = '';
    files.forEach(function (file) { previews.appendChild(preview(file)); });
    previews.hidden = files.length === 0;
    if (files.length) {
      previews.setAttribute('role', 'group');
      previews.setAttribute('aria-label', files.length === 1 ? text('msgSelectedOne') : text('msgSelected', { count: files.length }));
    }
  }

  input.addEventListener('change', update);
  // A photo dropped onto the zone lands on the input itself (it covers the zone), which fires change.
  if (input.files && input.files.length) {
    update();
  }
}

document.querySelectorAll('.quote-upload input[type="file"][data-max-files]').forEach(initPhotoField);
