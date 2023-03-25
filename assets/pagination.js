window.onload = () => {
    const Url = new URL(window.location.href);
    let page = document.querySelector('.page');
    let limit = document.querySelector('.limit');
    if (page && limit){
        page.value = 1

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

        const loadMore = document.querySelector('.load-more');
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