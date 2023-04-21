window.onload = () => {
    const Url = new URL(window.location.href);
    let page = document.querySelector('.page');
    let limit = document.querySelector('.limit');
    if (page && limit){
        page.value = 1
        const loadMore = document.querySelector('.load-more');
        if(parseInt(limit.value) === 1){
            loadMore.remove()
        }
        fetch(Url.pathname + "?page=" + page.value.toString() + "&ajax=1", {
            headers: {
                "X-Requested-With": "XMLHttpRequest"
            }
        }).then(
            response => response.json()
        ).then(
            data =>{
                const content = document.querySelector("#content");
                content.innerHTML = data.content;
                getDelete();
            }).catch(e => alert(e));
        loadMore.addEventListener('click', () => {
            let nextPage = ++ page.value

            const Url = new URL(window.location.href);
            fetch(Url.pathname + "?page=" + nextPage.toString() + "&ajax=1", {
                headers: {
                    "X-Requested-With": "XMLHttpRequest"
                }
            })
                .then(response => response.json())
                .then(
                    data =>{
                        const content = document.querySelector("#content");
                        content.innerHTML += data.content;
                        getDelete();

                    }
                ).catch(e => alert(e));
            // On modifie le numero de la page
            if(page.value === limit.value){
                loadMore.remove()
            }
        })
    }
    const postMedias = document.querySelector('#post-medias');
    const loadMedias = document.querySelector('.load-medias');
    loadMedias.addEventListener('click', (e)=>{
        e.preventDefault();
        postMedias.classList.remove('md:hidden');
        loadMedias.remove();
    })
}

 function getDelete() {
    let links = document.querySelectorAll("[data-delete]");

    for (let link of links){
        link.addEventListener('click', function (e) {
            e.preventDefault()
          return  confirm("Etes vous sur de vouloir supprimer cette figure?")
        })
    }
 }
