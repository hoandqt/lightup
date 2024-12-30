/* Regenerate content from existing content 
*
* @param
* type: the text type which can be html, longtext (multiple) or text.
* selector: the selector to return the content after generation.
* element: the dom element that called this function.
*/
function regenerate(type, selector, element) {

  // The input text to regenerate
  const input = document.querySelector(selector).value;

  // Get the parent element of the clicked button
  const parent = element.parentElement;

  var inputWord = '';

  // Find the previous input field with class "word-number-required"
  if (parent.querySelector('.word-number-required')) {
   inputWord = parent.querySelector('.word-number-required').value;
  }

  if (!input) {
      alert('Please fill out the content before generating.');
      return;
  }
  
  document.getElementById('content-loading').textContent = 'Working on content...';
  document.getElementById('content-loading').classList.remove('hidden');

  fetch('/re-generate', {
      method: 'POST',
      headers: {
          'Content-Type': 'application/json',
      },
      body: JSON.stringify({ input, type, inputWord, selector }),
  })
  .then(response => response.json())
  .then(data => {
      console.log('input text', input);
      console.log(data);
      document.getElementById('content-loading').textContent = '';
      document.getElementById('content-loading').classList.add('hidden');
      if (data.success) {
          document.querySelector(selector).value = data.result;
      } else {
          alert('Failed to generate content: ' + data.message);
      }
  })
  .catch(error => {
      document.getElementById('content-loading').textContent = '';
      document.getElementById('content-loading').classList.add('hidden');
      console.error('Error generating content:', error);
      alert('An error occurred while generating content.');
  });
}
