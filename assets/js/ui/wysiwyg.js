$( document ).ready( () => {
    const wysiwygs = Array.from( document.querySelectorAll( '.wysiwyg-editor' ) );

    if ( wysiwygs.length > 0 ) {
        import( /* webpackChunkName: "ckeditor" */ '@telabotanica/ckeditor5-build-ods' ).then( ( { default: ClassicEditor } ) => {
            wysiwygs.forEach( ( wysiwyg ) => {
                const textarea    = wysiwyg.querySelector( 'textarea' );
                textarea.required = false;

                let toolbar = [
                    'heading', '|',
                    'bold', 'italic', 'underline', 'link', 'bulletedList', 'numberedList', '|',
                    'fontFamily', 'fontSize', 'fontColor', '|',
                    'removeFormat', '|',
                    'alignment', 'indent', 'outdent', '|',
                    'blockQuote', 'insertTable', 'mediaEmbed', '|',
                    'undo', 'redo'
                ];

                let simpleUpload = {};

                if ( wysiwyg.getAttribute( 'data-upload' ) ) {
                    toolbar = [
                        'heading', '|',
                        'bold', 'italic', 'underline', 'link', 'bulletedList', 'numberedList', '|',
                        'fontFamily', 'fontSize', 'fontColor', '|',
                        'removeFormat', '|',
                        'alignment', 'indent', 'outdent', '|',
                        'imageUpload', 'blockQuote', 'insertTable', 'mediaEmbed', '|',
                        'undo', 'redo'
                    ];

                    simpleUpload = {
                        uploadUrl: wysiwyg.getAttribute( 'data-upload' ),
                    }
                }

                ClassicEditor
                    .create( textarea, {
                        toolbar:      toolbar,
                        simpleUpload: simpleUpload,
                        height:       '500px',
                    } )
                    .then( editor => {
                        console.log( Array.from( editor.ui.componentFactory.names() ) );

                        editor.on( 'required', ( evt ) => {
                            alert( 'This field is required.' );
                            evt.cancel();
                        } );
                    } )
                    .catch( error => {
                        console.error( error );
                    } );
            } );
        } );
    }
} );
